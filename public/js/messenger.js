/**
 * messenger.js
 * 
 * Module JavaScript cho tính năng Facebook Messenger Chat trong L.A.N ERP.
 * Kiến trúc: Object pattern để tránh xung đột với các script khác trên trang.
 * 
 * Chức năng:
 * 1. Điều hướng AJAX giữa các hội thoại (không reload trang)
 * 2. Polling tin nhắn mới mỗi 5 giây
 * 3. Gửi tin nhắn văn bản
 * 4. Gán nhân sự chăm sóc
 * 5. Quản lý Tags (Gắn nhãn, tạo nhãn mới)
 * 6. Infinite scroll tải tin nhắn cũ
 */

const MessengerApp = (function() {

    // --- Biến trạng thái nội bộ ---
    let _selectedPsid  = '';
    let _lastMsgId     = 0;
    let _hasMoreMsgs   = true;
    let _isLoadingMore = false;
    let _pollInterval  = null;
    let _urls          = {};

    // =========================================================
    // KHỞI TẠO
    // =========================================================
    function init(config) {
        _selectedPsid = config.selectedPsid || '';
        _lastMsgId    = config.lastMsgId    || 0;
        _urls         = config.urls         || {};

        _scrollToBottom();
        _initTagStyles();
        _startPolling();
        _bindEvents();
    }

    // =========================================================
    // UI HELPERS
    // =========================================================
    function _scrollToBottom() {
        const el = document.getElementById('chatMessages');
        if (el) el.scrollTop = el.scrollHeight;
    }

    function _escapeHtml(text) {
        if (!text) return '';
        const map = { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function _formatDate(dateStr) {
        const d = new Date(dateStr);
        const h = String(d.getHours()).padStart(2,'0');
        const m = String(d.getMinutes()).padStart(2,'0');
        const day = String(d.getDate()).padStart(2,'0');
        const month = String(d.getMonth()+1).padStart(2,'0');
        return `${h}:${m} ${day}/${month}/${d.getFullYear()}`;
    }

    function _renderAttachments(msg) {
        if (!msg.attachments) return '';
        let attachments;
        try { attachments = JSON.parse(msg.attachments); } catch(e) { return ''; }
        if (!attachments || !attachments.length) return '';

        let html = '<div class="message-attachments" style="margin-top:8px;display:flex;flex-direction:column;gap:8px;">';
        attachments.forEach(a => {
            if (a.type === 'image') {
                const src = (a.payload && a.payload.url) ? a.payload.url : (a.url || '');
                if (src) html += `<div class="attach-image"><img src="${src}" style="max-width:220px;border-radius:8px;cursor:pointer;" onclick="window.open('${src}')"></div>`;
            } else if (['file','video','audio'].includes(a.type)) {
                const icons = {video:'fa-video', audio:'fa-headphones', file:'fa-file-download'};
                const name = (a.payload && a.payload.name) ? a.payload.name : a.type;
                html += `<div class="attach-file" style="background:rgba(24,119,242,0.08);padding:8px;border-radius:6px;display:flex;align-items:center;gap:10px;">
                    <i class="fas ${icons[a.type]||'fa-file'}" style="font-size:20px;color:#1877f2;"></i>
                    <div style="font-size:13px;font-weight:500;">${_escapeHtml(name)}</div>
                </div>`;
            }
        });
        html += '</div>';
        return html;
    }

    function _renderMessageBubble(msg) {
        const isReceived = (msg.sender_type === 'user');
        return `<div class="message-bubble ${isReceived ? 'received' : 'sent'}" data-msg-id="${msg.id}">
            <div class="message-content">
                ${_escapeHtml(msg.message_text)}
                ${_renderAttachments(msg)}
            </div>
            <div class="message-time">${_formatDate(msg.created_at)}</div>
        </div>`;
    }

    // =========================================================
    // ĐIỀU HƯỚNG AJAX
    // =========================================================
    function _loadChatContent(url, pushState = true) {
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            beforeSend: () => {
                $('#messengerSidebar, #messengerMainChat').css('opacity', '0.6');
            },
            success: function(res) {
                $('#messengerSidebar').html(res.sidebar_html).css('opacity', '1');
                $('#messengerMainChat').html(res.chat_area_html).css('opacity', '1');
                _selectedPsid = res.selectedPsid;
                _lastMsgId    = res.lastMsgId;
                _hasMoreMsgs  = true;
                _isLoadingMore = false;
                if (pushState) {
                    history.pushState({ url }, '', url);
                }
                _scrollToBottom();
                _initTagStyles();
            },
            error: () => {
                $('#messengerSidebar, #messengerMainChat').css('opacity', '1');
                alert('Không thể tải nội dung. Vui lòng thử lại.');
            }
        });
    }

    // =========================================================
    // POLLING TIN NHẮN MỚI (mỗi 5 giây)
    // =========================================================
    function _startPolling() {
        if (_pollInterval) clearInterval(_pollInterval);
        _pollInterval = setInterval(_poll, 5000);
    }

    function _poll() {
        if (!_selectedPsid) return;
        const params = new URLSearchParams(window.location.search);

        $.ajax({
            url: _urls.ajaxChat,
            type: 'GET',
            data: {
                psid:         _selectedPsid,
                last_msg_id:  _lastMsgId,
                search:       params.get('search') || '',
                filter_staff: params.get('filter_staff') || '',
                filter_tag:   params.get('filter_tag') || ''
            },
            success: function(res) {
                // 1. Thêm tin nhắn mới vào khung chat
                if (res.new_messages && res.new_messages.length > 0) {
                    res.new_messages.forEach(msg => {
                        _lastMsgId = msg.id;
                        $('#chatMessages').append(_renderMessageBubble(msg));
                    });
                    _scrollToBottom();
                }

                // 2. Cập nhật preview danh sách hội thoại ở sidebar
                if (res.contacts && res.contacts.length > 0) {
                    res.contacts.forEach(c => {
                        const item = $(`.msn-conv-link[data-psid="${c.psid}"]`);
                        if (!item.length) return;
                        item.find('.conversation-preview').text(c.last_message);
                        item.find('.conversation-time').text(c.last_time);
                        const badge = item.find('.unread-badge');
                        if (c.unread_count > 0) {
                            if (badge.length) badge.text(c.unread_count);
                            else item.find('.conversation-name').append(`<span class="unread-badge">${c.unread_count}</span>`);
                        } else {
                            badge.remove();
                        }
                    });
                }
            }
        });
    }

    // =========================================================
    // GỬI TIN NHẮN
    // =========================================================
    function _sendMessage() {
        const message = $('.chat-input').val().trim();
        if (!message || !_selectedPsid) return;

        $('.btn-send').prop('disabled', true);

        $.ajax({
            url: _urls.sendMessage,
            type: 'POST',
            data: { psid: _selectedPsid, message: message },
            success: function(res) {
                if (res.status === 'success') {
                    $('.chat-input').val('');
                    // Tin nhắn sẽ xuất hiện qua polling
                } else {
                    alert('Lỗi: ' + (res.message || 'Không xác định'));
                }
                $('.btn-send').prop('disabled', false);
            },
            error: () => {
                alert('Lỗi kết nối mạng.');
                $('.btn-send').prop('disabled', false);
            }
        });
    }

    // =========================================================
    // TAGS
    // =========================================================
    function _initTagStyles() {
        $('.tag-checkbox').each(function() {
            const span = $(this).siblings('.tag-option');
            if ($(this).is(':checked')) {
                span.css({ background: '#1877f2', color: '#fff', borderColor: '#1877f2' });
            } else {
                span.css({ background: 'transparent', color: 'inherit', borderColor: '#cbd5e1' });
            }
        });
    }

    function saveTags(contactId) {
        const selectedTags = [];
        $('.tag-checkbox:checked').each(function() { selectedTags.push($(this).val()); });

        $.post(_urls.updateTags, { contact_id: contactId, tags: selectedTags }, function(res) {
            if (res.status === 'success') {
                let panelHtml = '';
                if (res.tags && res.tags.length > 0) {
                    res.tags.forEach(t => { panelHtml += `<span class="tag-badge">#${t}</span> `; });
                } else {
                    panelHtml = '<span class="tag-badge" style="background:#e2e8f0;color:#64748b;">Chưa có nhãn</span>';
                }
                $('#currentTags').html(panelHtml);

                const headerTags = $('#chatHeaderTags');
                if (headerTags.length) {
                    let hHtml = '';
                    if (res.tags && res.tags.length > 0) {
                        res.tags.slice(0, 3).forEach(t => { hHtml += `<span class="tag-badge" style="font-size:11px;padding:2px 8px;">#${t}</span>`; });
                        if (res.tags.length > 3) hHtml += `<span style="font-size:11px;color:#94a3b8;">+${res.tags.length - 3}</span>`;
                    }
                    headerTags.html(hHtml);
                }

                const sidebarMeta = $(`.msn-conv-link[data-psid="${_selectedPsid}"] .conversation-meta`);
                if (sidebarMeta.length) {
                    sidebarMeta.find('.conv-tag-badge, .extra-tag-count').remove();
                    if (res.tags && res.tags.length > 0) {
                        res.tags.slice(0, 2).forEach(t => { sidebarMeta.append(`<span class="conv-tag-badge">#${t}</span>`); });
                        if (res.tags.length > 2) sidebarMeta.append(`<span class="extra-tag-count" style="font-size:10px;color:#94a3b8;">+${res.tags.length - 2}</span>`);
                    }
                }
                $('#tagEditModal').hide();
            } else {
                alert('Lỗi: ' + res.message);
            }
        });
    }

    function createNewTag() {
        const name = $('#newTagInput').val().trim();
        if (!name) { alert('Vui lòng nhập tên nhãn.'); return; }

        $.post(_urls.createTag, { name }, function(res) {
            if (res.status === 'success') {
                const html = `<label style="cursor:pointer;margin:0;">
                    <input type="checkbox" class="tag-checkbox" value="${res.tag.name}" checked style="display:none;">
                    <span class="tag-option" style="padding:4px 12px;border-radius:20px;border:1px solid #1877f2;font-size:12px;transition:all .2s;display:inline-block;background:#1877f2;color:#fff;">#${res.tag.name}</span>
                </label>`;
                const container = $('#tagCheckboxList');
                container.find('.no-tags-msg').remove();
                container.append(html);
                $('#newTagInput').val('');
                $('select[name="filter_tag"]').append(`<option value="${res.tag.name}">#${res.tag.name}</option>`);
            } else {
                alert('Lỗi: ' + res.message);
            }
        });
    }

    // =========================================================
    // QUICK REPLY
    // =========================================================
    function useQuickReply(content) {
        $('.chat-input').val(content);
        $('#quickReplyModal').fadeOut();
        $('.chat-input').focus();
    }
    window.useQuickReply = useQuickReply;

    /** Tìm kiếm câu trả lời nhanh qua AJAX và hiển thị */
    function _searchQuickReplies(query) {
        const resultsContainer = $('#qrSearchResults');
        resultsContainer.html('<div style="text-align: center; padding: 20px; color: #94a3b8;"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>');

        $.ajax({
            url: '/zalo/quick-replies/search',
            type: 'GET',
            data: { q: query },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success' && res.data && res.data.length > 0) {
                    let html = '';
                    res.data.forEach(function(qr) {
                        const escapedContent = qr.content.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
                        html += `
                            <div class="quick-reply-item" onclick="useQuickReply('${escapedContent}')" style="padding: 10px 14px; border-radius: 8px; cursor: pointer; border: 1px solid #e2e8f0; margin-bottom: 8px; transition: all 0.2s;">
                                <div class="quick-reply-title" style="font-weight: 600; font-size: 13px; color: #1877f2;">${_escapeHtml(qr.title)}</div>
                                <div class="quick-reply-preview" style="font-size: 12px; color: #64748b; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${_escapeHtml(qr.content)}</div>
                            </div>
                        `;
                    });
                    resultsContainer.html(html);
                } else {
                    resultsContainer.html('<p class="chat-empty-list" style="text-align: center; color: #94a3b8; font-size: 13px; padding: 20px;">Không tìm thấy câu trả lời nào.</p>');
                }
            },
            error: function() {
                resultsContainer.html('<p class="chat-empty-list" style="text-align: center; color: #ef4444; font-size: 13px; padding: 20px;">Lỗi kết nối tải câu trả lời.</p>');
            }
        });
    }

    // =========================================================
    // LOAD MORE (Infinite Scroll tin nhắn cũ)
    // =========================================================
    function _loadMoreMessages() {
        if (!_selectedPsid || _isLoadingMore || !_hasMoreMsgs) return;
        const firstMsg = $('#chatMessages .message-bubble').first();
        if (!firstMsg.length) return;
        const beforeId = firstMsg.data('msg-id');
        if (!beforeId) return;

        _isLoadingMore = true;
        const oldHeight = document.getElementById('chatMessages').scrollHeight;

        $.ajax({
            url: _urls.loadMore,
            type: 'GET',
            data: { psid: _selectedPsid, before_id: beforeId },
            success: function(res) {
                if (res.messages && res.messages.length > 0) {
                    let html = '';
                    res.messages.forEach(msg => { html += _renderMessageBubble(msg); });
                    $('#chatMessages').prepend(html);
                    const newHeight = document.getElementById('chatMessages').scrollHeight;
                    document.getElementById('chatMessages').scrollTop = newHeight - oldHeight;
                    if (res.messages.length < 10) _hasMoreMsgs = false;
                } else {
                    _hasMoreMsgs = false;
                }
                _isLoadingMore = false;
            },
            error: () => { _isLoadingMore = false; }
        });
    }

    // =========================================================
    // BIND EVENTS (Dùng Event Delegation để hoạt động với AJAX content)
    // =========================================================
    function _bindEvents() {
        // Mở Quick Reply modal và tự động tải 10 câu mới nhất
        $(document).on('click', '.fa-bolt', function() {
            $('#quickReplyModal').fadeIn();
            $('#qrSearchInput').val('');
            _searchQuickReplies('');
            setTimeout(function() {
                $('#qrSearchInput').focus();
            }, 100);
        });

        // Tìm kiếm câu trả lời nhanh AJAX khi nhập từ khóa
        let qrSearchTimeout = null;
        $(document).on('input', '#qrSearchInput', function() {
            const query = $(this).val().trim();
            clearTimeout(qrSearchTimeout);
            qrSearchTimeout = setTimeout(function() {
                _searchQuickReplies(query);
            }, 300);
        });

        // Click vào hội thoại
        $(document).on('click', '.msn-conv-link', function(e) {
            e.preventDefault();
            _loadChatContent($(this).attr('href'));
        });

        // Submit form filter
        $(document).on('submit', '#messengerFilterForm', function(e) {
            e.preventDefault();
            _loadChatContent(_urls.index + '?' + $(this).serialize());
        });

        // Thay đổi filter select
        $(document).on('change', '.filter-select-msn', function() {
            $('#messengerFilterForm').submit();
        });

        // Gửi tin nhắn bằng Enter
        $(document).on('keypress', '.chat-input', function(e) {
            if (e.which === 13) _sendMessage();
        });

        // Gửi tin nhắn bằng nút
        $(document).on('click', '.btn-send', _sendMessage);

        // Gán nhân sự
        $(document).on('change', '#staffAssignment', function() {
            const contactId = $(this).data('contact-id');
            const staffId   = $(this).val();
            $.post(_urls.assignStaff, { contact_id: contactId, staff_id: staffId }, function(res) {
                if (res.status !== 'success') alert('Lỗi: ' + res.message);
            });
        });

        // Toggle style tag checkbox
        $(document).on('change', '.tag-checkbox', function() {
            const span = $(this).siblings('.tag-option');
            if ($(this).is(':checked')) {
                span.css({ background: '#1877f2', color: '#fff', borderColor: '#1877f2' });
            } else {
                span.css({ background: 'transparent', color: 'inherit', borderColor: '#cbd5e1' });
            }
        });

        // Infinite scroll
        $(document).on('scroll', '#chatMessages', function() {
            if ($(this).scrollTop() === 0) _loadMoreMessages();
        });

        // Browser back button
        window.onpopstate = function(event) {
            const url = (event.state && event.state.url) ? event.state.url : window.location.href;
            _loadChatContent(url, false);
        };
    }

    // =========================================================
    // PUBLIC API
    // =========================================================
    return {
        init,
        saveTags,
        createNewTag,
        useQuickReply,
    };

})();

$(document).ready(function() {
    const configEl = document.getElementById('messengerAppConfig');
    if (!configEl || typeof MessengerApp === 'undefined') {
        return;
    }

    MessengerApp.init({
        selectedPsid: configEl.dataset.selectedPsid || '',
        lastMsgId: configEl.dataset.lastMsgId || 0,
        urls: {
            index: configEl.dataset.urlIndex || '',
            ajaxChat: configEl.dataset.urlAjaxChat || '',
            sendMessage: configEl.dataset.urlSendMessage || '',
            assignStaff: configEl.dataset.urlAssignStaff || '',
            updateTags: configEl.dataset.urlUpdateTags || '',
            createTag: configEl.dataset.urlCreateTag || '',
            loadMore: configEl.dataset.urlLoadMore || ''
        }
    });
});
