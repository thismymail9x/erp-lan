/**
 * chat.js
 *
 * Module JavaScript h?p nh?t cho Trung tï¿½fÂ¯ï¿½,Â¿ï¿½,Â½m Tu v?n Khï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ch hï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng (Zalo + Messenger).
 * Ki?n trï¿½fÂ¯ï¿½,Â¿ï¿½,Â½c: IIFE pattern d? trï¿½fÂ¯ï¿½,Â¿ï¿½,Â½nh xung d?t bi?n toï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n c?c.
 *
 * Ch?c nang chï¿½fÂ¯ï¿½,Â¿ï¿½,Â½nh:
 * 1. ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½i?u hu?ng AJAX gi?a cï¿½fÂ¯ï¿½,Â¿ï¿½,Â½c h?i tho?i (khï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng reload trang)
 * 2. Polling tin nh?n m?i m?i 5 giï¿½fÂ¯ï¿½,Â¿ï¿½,Â½y
 * 3. G?i tin nh?n van b?n
 * 4. Gï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n nhï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n s? cham sï¿½fÂ¯ï¿½,Â¿ï¿½,Â½c
 * 5. Qu?n lï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ Tags (g?n nhï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n, t?o nhï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n m?i)
 * 6. Infinite scroll t?i tin nh?n cu
 * 7. Upload media (ch? Zalo)
 * 8. ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½?ng b? l?ch s? & profile (ch? Zalo)
 * 9. Ghi nh?n cu?c g?i (ch? Zalo)
 * 10. Browser history pushState/popstate
 */

const ChatApp = (function() {
    'use strict';

    // --- Bi?n tr?ng thï¿½fÂ¯ï¿½,Â¿ï¿½,Â½i n?i b? ---
    let _selectedChannel   = '';
    let _selectedContactId = '';
    let _lastMsgId         = 0;
    let _hasMoreMsgs       = true;
    let _isLoadingMore     = false;
    let _pollInterval      = null;
    let _urls              = {};

    // =========================================================
    // KH?I T?O - ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½u?c g?i t? ChatApp.init() trong view
    // =========================================================
    function init(config) {
        _selectedChannel   = config.selectedChannel   || '';
        _selectedContactId = config.selectedContactId || '';
        _lastMsgId         = config.lastMsgId         || 0;
        _urls              = config.urls              || {};

        _scrollToBottom();
        _initTagStyles();
        _startPolling();
        _bindEvents();
    }

    // =========================================================
    // TI?N ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½CH GIAO DI?N
    // =========================================================

    /** Cu?n khung tin nh?n xu?ng cu?i cï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng */
    function _scrollToBottom() {
        const el = document.getElementById('chatMessages');
        if (el) el.scrollTop = el.scrollHeight;
    }

    /** Escape HTML d? trï¿½fÂ¯ï¿½,Â¿ï¿½,Â½nh XSS khi render tin nh?n client-side */
    function _escapeHtml(text) {
        if (!text) return '';
        const map = { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    /** ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½?nh d?ng ngï¿½fÂ¯ï¿½,Â¿ï¿½,Â½y gi? theo chu?n Vi?t Nam: HH:mm dd/MM/yyyy */
    function _formatDate(dateStr) {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        const h     = String(d.getHours()).padStart(2, '0');
        const m     = String(d.getMinutes()).padStart(2, '0');
        const day   = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        return h + ':' + m + ' ' + day + '/' + month + '/' + d.getFullYear();
    }

    // =========================================================
    // RENDER TIN NH?N (client-side cho polling & load more)
    // =========================================================

    /** Render ph?n dï¿½fÂ¯ï¿½,Â¿ï¿½,Â½nh kï¿½fÂ¯ï¿½,Â¿ï¿½,Â½m (hï¿½fÂ¯ï¿½,Â¿ï¿½,Â½nh ?nh, file, video, sticker, audio) */
    function _renderAttachments(msg) {
        if (!msg.attachments) return '';
        var attachments;
        try { attachments = JSON.parse(msg.attachments); } catch(e) { return ''; }
        if (!attachments) return '';

        // Call log du?c x? lï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ b?i _renderCallBubble, khï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng render ? dï¿½fÂ¯ï¿½,Â¿ï¿½,Â½y
        if (attachments.type === 'call_log') return '';

        if (!Array.isArray(attachments) || !attachments.length) return '';


        var html = '<div class="message-attachments">';
        attachments.forEach(function(a) {
            var aType = a.type || '';

            if (aType === 'image') {
                // Hï¿½fÂ¯ï¿½,Â¿ï¿½,Â½nh ?nh: uu tiï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n payload.url, fallback url g?c
                var src = (a.payload && a.payload.url) ? a.payload.url : (a.url || '');
                if (src) {
                    html += '<div class="attach-image"><img src="' + src + '" class="js-open-attachment" data-url="' + src + '"></div>';
                }
            } else if (aType === 'sticker') {
                // Sticker
                var stickerUrl = (a.payload && a.payload.url) ? a.payload.url : '';
                if (stickerUrl) {
                    html += '<div class="attach-sticker"><img src="' + stickerUrl + '"></div>';
                }
            } else if (['file', 'video', 'audio'].indexOf(aType) !== -1) {
                // File, video, audio
                var icons = { video: 'fa-video', audio: 'fa-headphones', file: 'fa-file-download' };
                var iconCls = icons[aType] || 'fa-file';
                var name = (a.payload && a.payload.name) ? a.payload.name : aType;
                var size = (a.payload && a.payload.size) ? a.payload.size : 0;
                var sizeStr = size > 1048576
                    ? (Math.round(size / 1048576 * 100) / 100) + ' MB'
                    : (Math.round(size / 1024 * 100) / 100) + ' KB';

                html += '<div class="attach-file">';
                html += '<i class="fas ' + iconCls + '" style="font-size:18px;color:#3b82f6;"></i>';
                html += '<div style="flex:1;overflow:hidden;">';
                html += '<div class="attach-file-name">' + _escapeHtml(name) + '</div>';
                if (size > 0) html += '<div class="attach-file-size">' + sizeStr + '</div>';
                html += '</div></div>';
            }
        });
        html += '</div>';
        return html;
    }

    /** Render 1 bubble tin nh?n hoï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n ch?nh */
    function _renderMessageBubble(msg) {
        // Ki?m tra xem cï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ ph?i call log system message khï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng
        if (msg.sender_type === 'system' && msg.attachments) {
            try {
                var callData = JSON.parse(msg.attachments);
                if (callData && callData.type === 'call_log') {
                    return _renderCallBubble(callData, msg);
                }
            } catch(e) { /* khï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng ph?i JSON h?p l?, b? qua */ }
        }

        var isReceived = (msg.sender_type === 'user');
        var staffHtml = '';
        if (!isReceived && msg.staff_name) {
            staffHtml = '<div class="message-staff-name">' + _escapeHtml(msg.staff_name) + '</div>';
        }
        return '<div class="message-bubble ' + (isReceived ? 'received' : 'sent') + '" data-msg-id="' + msg.id + '">'
            + staffHtml
            + '<div class="message-content">'
            + _escapeHtml(msg.message_text)
            + _renderAttachments(msg)
            + '</div>'
            + '<div class="message-time">' + _formatDate(msg.created_at) + '</div>'
            + '</div>';
    }

    /** Render call bubble d?c bi?t cho l?ch s? cu?c g?i */
    function _renderCallBubble(callData, msg) {
        var resultClass = {
            'answered'  : '',
            'no_answer' : 'call-no-answer',
            'callback'  : 'call-callback',
            'rejected'  : 'call-rejected'
        }[callData.result || 'answered'] || '';

        var resultIcon = {
            'answered'  : 'fa-phone',
            'no_answer' : 'fa-phone-slash',
            'callback'  : 'fa-redo',
            'rejected'  : 'fa-phone-slash'
        }[callData.result || 'answered'] || 'fa-phone';

        var details = [];
        if (callData.duration_text) details.push('Th\u1eddi l\u01b0\u1ee3ng: ' + _escapeHtml(callData.duration_text));
        if (callData.staff_name)   details.push('Nh\u00e2n s\u1ef1: ' + _escapeHtml(callData.staff_name));
        if (callData.notes)        details.push('Ghi ch\u00fa: ' + _escapeHtml(callData.notes));

        return '<div class="message-bubble system-message" data-msg-id="' + (msg.id || '') + '" style="display:flex;justify-content:center;padding:8px 16px;">'
            + '<div class="call-bubble ' + resultClass + '">'
            + '  <div class="call-bubble-icon"><i class="fas ' + resultIcon + '"></i></div>'
            + '  <div class="call-bubble-content">'
            + '    <div class="call-bubble-title">Cu\u1ed9c g\u1ecdi: ' + _escapeHtml(callData.result_label || 'Cu\u1ed9c g\u1ecdi') + '</div>'
            + (details.length ? '<div class="call-bubble-detail">' + details.join(' - ') + '</div>' : '')
            + '  </div>'
            + '</div>'
            + '</div>';
    }


    // =========================================================
    // ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½I?U HU?NG AJAX ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ Thay n?i dung sidebar + chat area
    // =========================================================
    function _loadChatContent(url, pushState) {
        if (typeof pushState === 'undefined') pushState = true;

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            beforeSend: function() {
                $('#chatSidebar, #chatMainArea').css('opacity', '0.5');
            },
            success: function(res) {
                $('#chatSidebar').html(res.sidebar_html).css('opacity', '1');
                $('#chatMainArea').html(res.chat_area_html).css('opacity', '1');

                // C?p nh?t bi?n tr?ng thï¿½fÂ¯ï¿½,Â¿ï¿½,Â½i t? response
                _selectedChannel   = res.selectedChannel   || '';
                _selectedContactId = res.selectedContactId || '';
                _lastMsgId         = res.lastMsgId         || 0;
                _hasMoreMsgs       = true;
                _isLoadingMore     = false;

                if (pushState) {
                    history.pushState({ url: url }, res.title || '', url);
                }
                if (res.title) {
                    document.title = res.title;
                }

                _scrollToBottom();
                _initTagStyles();

                // C?p nh?t tr?ng thï¿½fÂ¯ï¿½,Â¿ï¿½,Â½i grid cho mobile (hi?n/?n sidebar vs chat)
                var grid = document.querySelector('.chat-grid');
                if (grid) {
                    if (_selectedContactId) {
                        grid.classList.add('has-selected');
                    } else {
                        grid.classList.remove('has-selected');
                    }
                }
            },
            error: function() {
                $('#chatSidebar, #chatMainArea').css('opacity', '1');
                alert('Kh\u00f4ng th\u1ec3 t\u1ea3i n\u1ed9i dung. Vui l\u00f2ng th\u1eed l\u1ea1i.');
            }
        });
    }

    // =========================================================
    // POLLING TIN NH?N M?I ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ M?i 5 giï¿½fÂ¯ï¿½,Â¿ï¿½,Â½y
    // =========================================================
    function _startPolling() {
        if (_pollInterval) clearInterval(_pollInterval);
        _pollInterval = setInterval(_poll, 5000);
    }

    function _poll() {
        // Ch? poll khi dang ch?n 1 contact c? th?
        if (!_selectedContactId || !_selectedChannel) return;

        var params = new URLSearchParams(window.location.search);

        $.ajax({
            url: _urls.ajaxChat,
            type: 'GET',
            data: {
                channel:          params.get('channel') || 'all',
                selected_channel: _selectedChannel,
                contact_id:       _selectedContactId,
                last_msg_id:      _lastMsgId,
                search:           params.get('search')       || '',
                filter_staff:     params.get('filter_staff')  || '',
                filter_tag:       params.get('filter_tag')    || '',
                filter_creator:   params.get('filter_creator') || ''
            },
            success: function(res) {
                // 1. Thï¿½fÂ¯ï¿½,Â¿ï¿½,Â½m tin nh?n m?i vï¿½fÂ¯ï¿½,Â¿ï¿½,Â½o cu?i khung chat
                if (res.new_messages && res.new_messages.length > 0) {
                    res.new_messages.forEach(function(msg) {
                        _lastMsgId = msg.id;
                        $('#chatMessages').append(_renderMessageBubble(msg));
                    });
                    _scrollToBottom();
                }

                // 2. C?p nh?t preview ? sidebar (last message, th?i gian, badge)
                if (res.contacts && res.contacts.length > 0) {
                    res.contacts.forEach(function(c) {
                        var platformId = c.platform_id || c.zalo_id || c.psid || '';
                        var item = $('.conversation-link[data-contact-id="' + platformId + '"]');
                        if (!item.length) return;

                        item.find('.conversation-preview').text(c.last_message);
                        item.find('.conversation-time').text(c.last_time);

                        var badge = item.find('.unread-badge');
                        if (c.unread_count > 0) {
                            if (badge.length) {
                                badge.text(c.unread_count);
                            } else {
                                item.find('.conversation-right').append(
                                    '<span class="unread-badge">' + c.unread_count + '</span>'
                                );
                            }
                        } else {
                            badge.remove();
                        }
                        
                        // Update SLA Badges
                        var titleRow = item.find('.conversation-title-row');
                        titleRow.find('.badge-overdue').remove();
                        if (c.is_overdue) {
                            titleRow.append('<span class="badge-overdue pulse-alert" title="Qu\u00e1 h\u1ea1n ph\u1ea3n h\u1ed3i \u0111\u1ea7u ti\u00ean 2h!"><i class="fas fa-exclamation-triangle"></i></span>');
                        } else if (c.ongoing_is_overdue) {
                            titleRow.append('<span class="badge-overdue pulse-alert ongoing-overdue-badge" title="Qu\u00e1 h\u1ea1n ph\u1ea3n h\u1ed3i kh\u00e1ch \u0111ang trao \u0111\u1ed5i!"><i class="fas fa-exclamation-triangle"></i></span>');
                        }
                    });
                }
            }
        });
    }

    // =========================================================
    // G?I TIN NH?N ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ POST v?i channel + contact_id
    // =========================================================
    function _sendMessage() {
        var message = $('.chat-input').val().trim();
        if (!message || !_selectedContactId || !_selectedChannel) return;

        $('.btn-send').prop('disabled', true);

        $.ajax({
            url: _urls.sendMessage,
            type: 'POST',
            data: {
                channel:    _selectedChannel,
                contact_id: _selectedContactId,
                message:    message
            },
            success: function(res) {
                if (res.status === 'success') {
                    $('.chat-input').val('');
                    // Tin nh?n xu?t hi?n t?c thï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ b?ng cï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ch kï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ch ho?t poll ngay l?p t?c
                    _poll();
                } else {
                    alert('L\u1ed7i: ' + (res.message || 'Kh\u00f4ng x\u00e1c \u0111\u1ecbnh'));
                }
                $('.btn-send').prop('disabled', false);
            },
            error: function() {
                alert('L\u1ed7i k\u1ebft n\u1ed1i m\u1ea1ng.');
                $('.btn-send').prop('disabled', false);
            }
        });
    }

    // =========================================================
    // INFINITE SCROLL ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ T?i tin nh?n cu hon khi cu?n lï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n d?u
    // =========================================================
    function _loadMoreMessages() {
        if (!_selectedContactId || _isLoadingMore || !_hasMoreMsgs) return;

        var firstMsg = $('#chatMessages .message-bubble').first();
        if (!firstMsg.length) return;
        var beforeId = firstMsg.data('msg-id');
        if (!beforeId) return;

        _isLoadingMore = true;
        var chatEl = document.getElementById('chatMessages');
        var oldHeight = chatEl.scrollHeight;

        $.ajax({
            url: _urls.loadMore,
            type: 'GET',
            data: {
                channel:    _selectedChannel,
                contact_id: _selectedContactId,
                before_id:  beforeId
            },
            success: function(res) {
                if (res.messages && res.messages.length > 0) {
                    var html = '';
                    res.messages.forEach(function(msg) {
                        html += _renderMessageBubble(msg);
                    });
                    $('#chatMessages').prepend(html);

                    // Gi? v? trï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ scroll d? khï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng b? nh?y lï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n d?u
                    var newHeight = chatEl.scrollHeight;
                    chatEl.scrollTop = newHeight - oldHeight;

                    // N?u ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½t hon 10 tin ? dï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ h?t tin nh?n cu
                    if (res.messages.length < 10) _hasMoreMsgs = false;
                } else {
                    _hasMoreMsgs = false;
                }
                _isLoadingMore = false;
            },
            error: function() {
                _isLoadingMore = false;
            }
        });
    }

    // =========================================================
    // Gï¿½fÂ¯ï¿½,Â¿ï¿½,Â½N NHï¿½fÂ¯ï¿½,Â¿ï¿½,Â½N S? ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ POST staff assignment cho contact
    // =========================================================
    function _assignStaff(contactId, staffId) {
        $.post(_urls.assignStaff, {
            channel:    _selectedChannel,
            contact_id: contactId,
            staff_id:   staffId
        }, function(res) {
            if (res.status === 'success') {
                alert('\u0110\u00e3 c\u1eadp nh\u1eadt nh\u00e2n s\u1ef1 ph\u1ee5 tr\u00e1ch!');
            } else {
                alert('L\u1ed7i: ' + (res.message || 'Kh\u00f4ng x\u00e1c \u0111\u1ecbnh'));
            }
        });
    }

    // =========================================================
    // TAGS ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ G?n nhï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n & T?o nhï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n m?i
    // =========================================================

    /** Kh?i t?o style cho cï¿½fÂ¯ï¿½,Â¿ï¿½,Â½c checkbox tag dï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ check (g?i l?i sau AJAX) */
    function _initTagStyles() {
        $('.tag-checkbox').each(function() {
            var span = $(this).siblings('.tag-option');
            if ($(this).is(':checked')) {
                span.addClass('selected');
            } else {
                span.removeClass('selected');
            }
        });
    }

    /** Luu tags ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ public API, g?i t? onclick trong view */
    function saveTags(contactId) {
        var selectedTags = [];
        $('.tag-checkbox:checked').each(function() {
            selectedTags.push($(this).val());
        });

        $.post(_urls.updateTags, {
            channel:    _selectedChannel,
            contact_id: contactId,
            tags:       selectedTags
        }, function(res) {
            if (res.status === 'success') {
                // 1. C?p nh?t tags trong panel h? so (insight panel)
                var panelHtml = '';
                if (res.tags && res.tags.length > 0) {
                    res.tags.forEach(function(t) {
                        panelHtml += '<span class="tag-badge">#' + _escapeHtml(t) + '</span> ';
                    });
                } else {
                    panelHtml = '<span class="tag-badge tag-badge-empty">Ch\u01b0a c\u00f3 nh\u00e3n</span>';
                }
                $('#currentTags').html(panelHtml);

                // 2. C?p nh?t tags trong chat header
                var headerTags = $('#chatHeaderTags');
                if (headerTags.length) {
                    var hHtml = '';
                    if (res.tags && res.tags.length > 0) {
                        res.tags.slice(0, 3).forEach(function(t) {
                            hHtml += '<span class="tag-badge" style="font-size:10px;padding:2px 6px;">#' + _escapeHtml(t) + '</span>';
                        });
                        if (res.tags.length > 3) {
                            hHtml += '<span class="extra-tag-count">+' + (res.tags.length - 3) + '</span>';
                        }
                    }
                    headerTags.html(hHtml);
                }

                // 3. C?p nh?t tags trong sidebar
                var sidebarItem = $('.conversation-link[data-contact-id="' + _selectedContactId + '"] .conversation-meta');
                if (sidebarItem.length) {
                    sidebarItem.find('.conv-tag-badge, .extra-tag-count').remove();
                    if (res.tags && res.tags.length > 0) {
                        res.tags.slice(0, 2).forEach(function(t) {
                            sidebarItem.append('<span class="conv-tag-badge">#' + _escapeHtml(t) + '</span>');
                        });
                        if (res.tags.length > 2) {
                            sidebarItem.append('<span class="extra-tag-count">+' + (res.tags.length - 2) + '</span>');
                        }
                    }
                }

                $('#tagEditModal').hide();
                alert('\u0110\u00e3 c\u1eadp nh\u1eadt nh\u00e3n th\u00e0nh c\u00f4ng!');
            } else {
                alert('L\u1ed7i: ' + (res.message || ''));
            }
        });
    }

    /** T?o nhï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n m?i ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ public API, g?i t? onclick trong view */
    function createNewTag() {
        var name = $('#newTagInput').val().trim();
        if (!name) {
            alert('Vui l\u00f2ng nh\u1eadp t\u00ean nh\u00e3n.');
            return;
        }

        $.post(_urls.createTag, { name: name }, function(res) {
            if (res.status === 'success') {
                var html = '<label class="tag-option-label">'
                    + '<input type="checkbox" class="tag-checkbox" value="' + _escapeHtml(res.tag.name) + '" checked>'
                    + '<span class="tag-option selected">#' + _escapeHtml(res.tag.name) + '</span>'
                    + '</label>';
                var container = $('#tagCheckboxList');
                container.find('.no-tags-msg').remove();
                container.append(html);
                $('#newTagInput').val('');

                // C?p nh?t b? l?c nhï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n trong sidebar
                $('select[name="filter_tag"]').append(
                    '<option value="' + _escapeHtml(res.tag.name) + '">#' + _escapeHtml(res.tag.name) + '</option>'
                );
            } else {
                alert('L\u1ed7i: ' + (res.message || ''));
            }
        });
    }

    // =========================================================
    // QUICK REPLY ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ Chï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n n?i dung cï¿½fÂ¯ï¿½,Â¿ï¿½,Â½u tr? l?i nhanh vï¿½fÂ¯ï¿½,Â¿ï¿½,Â½o ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ input
    // =========================================================
    function useQuickReply(content) {
        $('.chat-input').val(content);
        $('#quickReplyModal').fadeOut();
        $('.chat-input').focus();
    }

    /** Tï¿½fÂ¯ï¿½,Â¿ï¿½,Â½m ki?m cï¿½fÂ¯ï¿½,Â¿ï¿½,Â½u tr? l?i nhanh qua AJAX vï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ hi?n th? */
    function _searchQuickReplies(query) {
        const resultsContainer = $('#qrSearchResults');
        resultsContainer.html('<div class="quick-reply-loading"><i class="fas fa-spinner fa-spin"></i> \u0110ang t\u1ea3i...</div>');

        $.ajax({
            url: '/zalo/quick-replies/search',
            type: 'GET',
            data: { q: query },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success' && res.data && res.data.length > 0) {
                    let html = '';
                    res.data.forEach(function(qr) {
                        const escapedContent = _escapeHtml(qr.content);
                        html += `
                            <div class="quick-reply-item js-use-quick-reply" data-content="${escapedContent}">
                                <div class="quick-reply-title">${_escapeHtml(qr.title)}</div>
                                <div class="quick-reply-preview">${_escapeHtml(qr.content)}</div>
                            </div>
                        `;
                    });
                    resultsContainer.html(html);
                } else {
                    resultsContainer.html('<p class="chat-empty-list quick-reply-empty">Kh\u00f4ng t\u00ecm th\u1ea5y c\u00e2u tr\u1ea3 l\u1eddi n\u00e0o.</p>');
                }
            },
            error: function() {
                resultsContainer.html('<p class="chat-empty-list quick-reply-error">L\u1ed7i k\u1ebft n\u1ed1i t\u1ea3i c\u00e2u tr\u1ea3 l\u1eddi.</p>');
            }
        });
    }

    // =========================================================
    // UPLOAD MEDIA ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ Ch? dï¿½fÂ¯ï¿½,Â¿ï¿½,Â½nh cho kï¿½fÂ¯ï¿½,Â¿ï¿½,Â½nh Zalo
    // =========================================================
    function _handleMediaUpload(input) {
        if (!input.files || !input.files[0] || !_selectedContactId) return;

        var file = input.files[0];
        var isImage = file.type.startsWith('image/');
        var maxSize = isImage ? 1 * 1024 * 1024 : 5 * 1024 * 1024; // 1MB ?nh, 5MB file

        if (file.size > maxSize) {
            alert('T\u1ec7p qu\u00e1 l\u1edbn. Gi\u1edbi h\u1ea1n: ' + (isImage ? '1MB' : '5MB') + '.');
            $(input).val('');
            return;
        }

        var formData = new FormData();
        formData.append('channel', _selectedChannel);
        formData.append('contact_id', _selectedContactId);
        formData.append('media', file);

        // Hi?u ?ng loading
        $('.chat-input-area').css('opacity', '0.5');

        $.ajax({
            url: _urls.uploadMedia,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.status === 'success') {
                    $(input).val('');
                    // Tin nh?n media xu?t hi?n t?c thï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ b?ng cï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ch kï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ch ho?t poll ngay l?p t?c
                    _poll();
                } else {
                    alert('L\u1ed7i: ' + (res.message || ''));
                }
                $('.chat-input-area').css('opacity', '1');
            },
            error: function() {
                alert('M\u1ea1ng kh\u00f4ng \u1ed5n \u0111\u1ecbnh khi t\u1ea3i l\u00ean.');
                $('.chat-input-area').css('opacity', '1');
            }
        });
    }

    // =========================================================
    // ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½?NG B? L?CH S? ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ Ch? Zalo, g?i API sync tin nh?n
    // =========================================================
    function _syncHistory(contactId) {
        if (!contactId || !_urls.syncHistory) return;

        var btn = $('.btn-sync-history');
        var icon = btn.find('i');
        icon.addClass('fa-spin');
        btn.prop('disabled', true);

        $.post(_urls.syncHistory, {
            channel:    'zalo',
            contact_id: contactId
        }, function(res) {
            icon.removeClass('fa-spin');
            btn.prop('disabled', false);
            if (res.status === 'success') {
                alert(res.message || '\u0110\u1ed3ng b\u1ed9 th\u00e0nh c\u00f4ng!');
                // Reload toï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n trang d? hi?n th? tin nh?n cu v?a d?ng b? v?.
                // _loadChatContent ch? l?y 30 tin m?i nh?t (ORDER BY id DESC LIMIT 30)
                // nï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n khï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng th? hi?n th? du?c tin nh?n l?ch s? cu hon t? Zalo.
                window.location.reload();
            } else {
                alert('\u0110\u1ed3ng b\u1ed9 th\u1ea5t b\u1ea1i: ' + (res.message || ''));
            }
        }).fail(function() {
            icon.removeClass('fa-spin');
            btn.prop('disabled', false);
            alert('L\u1ed7i k\u1ebft n\u1ed1i khi \u0111\u1ed3ng b\u1ed9. Vui l\u00f2ng th\u1eed l\u1ea1i.');
        });
    }

    // =========================================================
    // ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½?NG B? PROFILE ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ Ch? Zalo, c?p nh?t tï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n + avatar
    // =========================================================
    function _syncProfile(contactId) {
        if (!contactId || !_urls.syncProfile) return;

        var btn = $('.fa-sync-alt');
        btn.addClass('fa-spin');

        $.post(_urls.syncProfile, {
            channel:    'zalo',
            contact_id: contactId
        }, function(res) {
            if (res.status === 'success') {
                alert(res.message || '\u0110\u00e3 c\u1eadp nh\u1eadt profile!');
                _loadChatContent(window.location.href, false);
            } else {
                alert(res.message || 'C\u1eadp nh\u1eadt th\u1ea5t b\u1ea1i.');
            }
            btn.removeClass('fa-spin');
        });
    }

    // =========================================================
    // G?I ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½I?N (CALL MODAL V2) ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ H? tr? c? Zalo vï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ Messenger
    // =========================================================
    var _callTimer    = null;   // interval ID
    var _callSeconds  = 0;      // s? giï¿½fÂ¯ï¿½,Â¿ï¿½,Â½y dï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ g?i
    var _callStarted  = false;  // c? dang g?i

    /** ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½?nh d?ng giï¿½fÂ¯ï¿½,Â¿ï¿½,Â½y -> mm:ss */
    function _formatCallTime(secs) {
        var m = Math.floor(secs / 60);
        var s = secs % 60;
        return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }

    /** M? Call Modal, di?n thï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng tin khï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ch hï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng */
    function openCallModal() {
        if (!_selectedContactId) return;

        // L?y thï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng tin khï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ch hï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng t? DOM chat header
        var avatarSrc = $('.chat-header .chat-avatar').attr('src')
            || 'https://ui-avatars.com/api/?name=KH&background=random';
        var contactName = $('.chat-header [style*="font-weight: 600"]').first().text().trim()
            || 'Kh\u00e1ch h\u00e0ng';
        var phoneText = $('#leadPhone').val().trim() || '--';

        // Reset tr?ng thï¿½fÂ¯ï¿½,Â¿ï¿½,Â½i
        _callSeconds  = 0;
        _callStarted  = false;
        if (_callTimer) { clearInterval(_callTimer); _callTimer = null; }

        // ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½i?n thï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng tin vï¿½fÂ¯ï¿½,Â¿ï¿½,Â½o modal
        $('#callModalAvatar').attr('src', avatarSrc);
        $('#callModalName').text(contactName);
        $('#callModalPhone').text(phoneText);
        $('#callTimer').text('00:00');
        $('#callTimerWrap').hide();
        $('#callStatusText').text('\u0110ang chu\u1ea9n b\u1ecb k\u1ebft n\u1ed1i...');
        $('#callModalBody').hide();
        $('#callModalActions').show();
        $('#callNotes').val('');
        $('input[name="call_result"][value="answered"]').prop('checked', true);

        // Hi?n th? modal
        $('#callModal').css('display', 'flex');

        // T? d?ng b?t d?u timer sau 1s (gi? l?p cu?c g?i b?t d?u)
        setTimeout(function() {
            _callStarted = true;
            $('#callStatusText').text('\u0110ang trong cu\u1ed9c g\u1ecdi...');
            $('#callTimerWrap').fadeIn(300);
            _callTimer = setInterval(function() {
                _callSeconds++;
                $('#callTimer').text(_formatCallTime(_callSeconds));
            }, 1000);
        }, 1000);
    }

    /** K?t thï¿½fÂ¯ï¿½,Â¿ï¿½,Â½c cu?c g?i ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ d?ng timer, hi?n th? form ghi nh?n */
    function _endCall() {
        if (_callTimer) { clearInterval(_callTimer); _callTimer = null; }
        _callStarted = false;
        $('#callStatusText').text('Cu\u1ed9c g\u1ecdi k\u1ebft th\u00fac - ' + _formatCallTime(_callSeconds));
        $('#callModalActions').hide();
        $('#callModalBody').fadeIn(200);
    }

    /** Luu l?ch s? cu?c g?i qua API V2 */
    function _saveCallLog() {
        if (!_selectedContactId) return;

        var callResult = $('input[name="call_result"]:checked').val() || 'answered';
        var notes      = $('#callNotes').val().trim();
        var duration   = _callSeconds;
        var channel    = _selectedChannel || 'zalo';

        var $btn = $('#btnSaveCall');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> \u0110ang l\u01b0u...');

        $.post(_urls.logCallV2, {
            channel:     channel,
            contact_id:  _selectedContactId,
            call_result: callResult,
            duration:    duration,
            notes:       notes
        }, function(res) {
            $btn.prop('disabled', false).html('<i class="fas fa-save"></i> L\u01b0u l\u1ecbch s\u1eed cu\u1ed9c g\u1ecdi');
            if (res.status === 'success') {
                $('#callModal').fadeOut(200);
                _callSeconds = 0;
                // Kï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ch ho?t poll d? hi?n th? call bubble ngay l?p t?c
                _poll();
            } else {
                alert('L\u1ed7i: ' + (res.message || 'Kh\u00f4ng th\u1ec3 l\u01b0u l\u1ecbch s\u1eed.'));
            }
        }).fail(function() {
            $btn.prop('disabled', false).html('<i class="fas fa-save"></i> L\u01b0u l\u1ecbch s\u1eed cu\u1ed9c g\u1ecdi');
            alert('L\u1ed7i k\u1ebft n\u1ed1i m\u1ea1ng. Vui l\u00f2ng th\u1eed l\u1ea1i.');
        });
    }


    // BIND EVENTS ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ Dï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng Event Delegation cho n?i dung AJAX
    // =========================================================
    function _bindEvents() {

        // --- Click vï¿½fÂ¯ï¿½,Â¿ï¿½,Â½o h?i tho?i trong sidebar ---
        $(document).on('click', '.conversation-link', function(e) {
            if ($(e.target).closest('input, button, label').length) {
                return;
            }
            e.preventDefault();
            _loadChatContent($(this).attr('href'));
        });

        // --- Submit form b? l?c sidebar ---
        $(document).on('submit', '#filterForm', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            _loadChatContent(_urls.index + '?' + formData);
        });

        // --- Thay d?i filter select ? auto submit form ---
        $(document).on('change', '.chat-filter-select', function() {
            $('#filterForm').submit();
        });

        $(document).on('change', '#chatSelectAll', function() {
            $('.chat-contact-checkbox').prop('checked', $(this).is(':checked'));
            _updateBulkDeleteState();
        });

        $(document).on('change', '.chat-contact-checkbox', function() {
            var total = $('.chat-contact-checkbox').length;
            var checked = $('.chat-contact-checkbox:checked').length;
            $('#chatSelectAll').prop('checked', total > 0 && total === checked);
            _updateBulkDeleteState();
        });

        $(document).on('click', '#chatBulkDeleteBtn', function() {
            _bulkDeleteContacts();
        });

        // --- G?i tin nh?n b?ng phï¿½fÂ¯ï¿½,Â¿ï¿½,Â½m Enter ---
        $(document).on('keypress', '.chat-input', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                _sendMessage();
            }
        });

        // --- G?i tin nh?n b?ng nï¿½fÂ¯ï¿½,Â¿ï¿½,Â½t b?m ---
        $(document).on('click', '.btn-send', function() {
            _sendMessage();
        });

        // --- Gï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n nhï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n s? ph? trï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ch ---
        $(document).on('change', '#staffAssignment, #staffAssignmentMobile', function() {
            var contactId = $(this).data('contact-id');
            var staffId   = $(this).val();
            _assignStaff(contactId, staffId);
        });

        // --- Toggle style tag checkbox ---
        $(document).on('change', '.tag-checkbox', function() {
            var span = $(this).siblings('.tag-option');
            if ($(this).is(':checked')) {
                span.addClass('selected');
            } else {
                span.removeClass('selected');
            }
        });

        // --- Infinite scroll: cu?n lï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n d?u d? t?i tin cu ---
        $(document).on('scroll', '#chatMessages', function() {
            if ($(this).scrollTop() === 0) {
                _loadMoreMessages();
            }
        });

        // --- Upload media (Zalo) ---
        $(document).on('change', '#mediaUpload, #imageUpload', function() {
            _handleMediaUpload(this);
        });

        // --- ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½?ng b? tin nh?n Zalo ---
        $(document).on('click', '.btn-sync-history', function() {
            var cid = $(this).data('contact-id');
            _syncHistory(cid);
        });

        // --- ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½?ng b? profile Zalo ---
        $(document).on('click', '.btn-sync-profile', function() {
            var cid = $(this).data('contact-id');
            _syncProfile(cid);
        });

        // --- Ghi nh?n cu?c g?i ---
        $(document).on('click', '#btnSubmitCall', function() {
            _submitCallLog();
        });

        // --- M? Quick Reply modal ---
        $(document).on('click', '.btn-quick-reply', function() {
            $('#quickReplyModal').fadeIn();
            $('#qrSearchInput').val('');
            _searchQuickReplies('');
            setTimeout(function() {
                $('#qrSearchInput').focus();
            }, 100);
        });

        // --- Tï¿½fÂ¯ï¿½,Â¿ï¿½,Â½m ki?m cï¿½fÂ¯ï¿½,Â¿ï¿½,Â½u tr? l?i nhanh AJAX khi nh?p t? khï¿½fÂ¯ï¿½,Â¿ï¿½,Â½a ---
        let qrSearchTimeout = null;
        $(document).on('input', '#qrSearchInput', function() {
            const query = $(this).val().trim();
            clearTimeout(qrSearchTimeout);
            qrSearchTimeout = setTimeout(function() {
                _searchQuickReplies(query);
            }, 300);
        });

        // --- M? Tag Edit modal ---
        $(document).on('click', '.btn-open-tags', function() {
            $('#tagEditModal').css('display', 'flex');
        });

        // --- M? Insight Panel ---
        $(document).on('click', '.btn-toggle-insight', function() {
            document.getElementById('insightPanel').classList.toggle('open');
        });

        // --- ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng Insight Panel ---
        $(document).on('click', '.insight-close', function() {
            document.getElementById('insightPanel').classList.remove('open');
        });

        // --- ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng modal Tag Edit ---
        $(document).on('click', '#tagEditModal .modal-close', function() {
            $('#tagEditModal').hide();
        });

        // --- ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng Quick Reply modal ---
        $(document).on('click', '#quickReplyModal .modal-close', function() {
            $('#quickReplyModal').fadeOut();
        });

        // --- ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng Call Log modal ---
        $(document).on('click', '#callLogModal .modal-close', function() {
            $('#callLogModal').fadeOut();
        });

        // --- M? Call Log modal ---
        $(document).on('click', '.btn-open-calllog', function() {
            $('#callLogModal').css('display', 'flex');
        });

        // --- K?t thï¿½fÂ¯ï¿½,Â¿ï¿½,Â½c cu?c g?i ---
        $(document).on('click', '#btnEndCall', function() {
            _endCall();
        });

        // --- Luu l?ch s? sau khi g?i ---
        $(document).on('click', '#btnSaveCall', function() {
            _saveCallLog();
        });

        // --- H?y vï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ dï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng Call Modal ---
        $(document).on('click', '#btnCancelCall', function() {
            if (_callTimer) { clearInterval(_callTimer); _callTimer = null; }
            _callSeconds = 0;
            $('#callModal').fadeOut(200);
        });

        // --- ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng Call Modal khi click backdrop ---
        $(document).on('click', '#callModal', function(e) {
            if ($(e.target).is('#callModal') && !_callStarted) {
                $('#callModal').fadeOut(200);
            }
        });

        // --- M? Call Modal m?i ---
        $(document).on('click', '.btn-open-callmodal', function() {
            openCallModal();
        });

        // --- Backward compat: n?u v?n cï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ nï¿½fÂ¯ï¿½,Â¿ï¿½,Â½t .btn-open-calllog cu ---
        $(document).on('click', '.btn-open-calllog', function() {
            openCallModal();
        });

        // --- Ghi nh?n cu?c g?i cu (fallback) ---
        $(document).on('click', '#btnSubmitCall', function() {
            _saveCallLog();
        });

        // --- Click nï¿½fÂ¯ï¿½,Â¿ï¿½,Â½t back trï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n mobile ---
        $(document).on('click', '.btn-back-mobile', function(e) {
            e.preventDefault();
            
            // Xï¿½fÂ¯ï¿½,Â¿ï¿½,Â½a class has-selected d? hi?n l?i danh sï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ch (sidebar) trï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n mobile
            var grid = document.querySelector('.chat-grid');
            if (grid) {
                grid.classList.remove('has-selected');
            }
            
            // C?p nh?t l?i URL trï¿½fÂ¯ï¿½,Â¿ï¿½,Â½nh duy?t (b? selected_channel vï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ contact_id) d? d?ng b? tr?ng thï¿½fÂ¯ï¿½,Â¿ï¿½,Â½i
            var currentUrl = new URL(window.location.href);
            currentUrl.searchParams.delete('selected_channel');
            currentUrl.searchParams.delete('contact_id');
            history.pushState({ url: currentUrl.toString() }, document.title, currentUrl.toString());
            
            // Reset bi?n tr?ng thï¿½fÂ¯ï¿½,Â¿ï¿½,Â½i h?i tho?i hi?n t?i
            _selectedContactId = '';
            _selectedChannel = '';
        });

        // --- Browser Back/Forward ---
        window.onpopstate = function(event) {
            var url = (event.state && event.state.url) ? event.state.url : window.location.href;
            _loadChatContent(url, false);
        };
    }

    function _getSelectedContacts() {
        var selected = [];
        $('.chat-contact-checkbox:checked').each(function() {
            selected.push({
                channel: $(this).data('channel'),
                contact_id: $(this).data('contact-id')
            });
        });
        return selected;
    }

    function _updateBulkDeleteState() {
        var count = $('.chat-contact-checkbox:checked').length;
        $('#chatSelectedCount').text('(' + count + ')');
        $('#chatBulkDeleteBtn').prop('disabled', count === 0);
    }

    function _bulkDeleteContacts() {
        var selected = _getSelectedContacts();
        if (!selected.length) return;

        if (!confirm('X\u00f3a ' + selected.length + ' h\u1ed9i tho\u1ea1i \u0111\u00e3 ch\u1ecdn?')) {
            return;
        }

        var $btn = $('#chatBulkDeleteBtn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> \u0110ang x\u00f3a...');

        $.ajax({
            url: _urls.bulkDelete,
            type: 'POST',
            dataType: 'json',
            data: {
                items: JSON.stringify(selected)
            },
            success: function(res) {
                if (res.status !== 'success') {
                    alert(res.message || 'Kh\u00f4ng th\u1ec3 x\u00f3a h\u1ed9i tho\u1ea1i.');
                    return;
                }

                var currentUrl = new URL(window.location.href);
                currentUrl.searchParams.delete('selected_channel');
                currentUrl.searchParams.delete('contact_id');
                _loadChatContent(currentUrl.toString());
            },
            error: function(xhr) {
                var message = 'Kh\u00f4ng th\u1ec3 x\u00f3a h\u1ed9i tho\u1ea1i.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                alert(message);
            },
            complete: function() {
                $btn.html('<i class="fas fa-trash"></i> X\u00f3a all');
                _updateBulkDeleteState();
            }
        });
    }

    /** C?p nh?t th? cï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng m?t tru?ng thu?c tï¿½fÂ¯ï¿½,Â¿ï¿½,Â½nh c?a Lead (d? nï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng, phone, email...) */
    function saveLeadField(fieldName) {
        var inputEl;
        var value;
        var channel = _selectedChannel;
        var recordId = '';

        if (fieldName === 'lead_warmth') {
            inputEl = $('#leadWarmthSelect');
            value = inputEl.val();
            recordId = inputEl.data('id');
            channel = inputEl.data('channel');
        } else if (fieldName === 'phone_number') {
            inputEl = $('#leadPhone');
            value = inputEl.val().trim();
            recordId = inputEl.data('id');
            channel = inputEl.data('channel');
        } else if (fieldName === 'email') {
            inputEl = $('#leadEmail');
            value = inputEl.val().trim();
            recordId = inputEl.data('id');
            channel = inputEl.data('channel');
        }

        if (!recordId || !channel) {
            alert('L\u1ed7i: Thi\u1ebfu ID ho\u1eb7c K\u00eanh kh\u00e1ch h\u00e0ng.');
            return;
        }

        var postData = {
            channel: channel,
            id: recordId
        };
        postData[fieldName] = value;

        $.ajax({
            url: _urls.index + '/update-insights',
            type: 'POST',
            data: postData,
            success: function(res) {
                if (res.status === 'success') {
                    // C?p nh?t l?i khung chat hi?n t?i mï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ khï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng lï¿½fÂ¯ï¿½,Â¿ï¿½,Â½m m?t h?i tho?i
                    _loadChatContent(window.location.href, false);
                    alert('\u0110\u00e3 c\u1eadp nh\u1eadt ' + (fieldName === 'lead_warmth' ? '\u0111\u1ed9 n\u00f3ng' : (fieldName === 'phone_number' ? 's\u1ed1 \u0111i\u1ec7n tho\u1ea1i' : 'email')) + ' th\u00e0nh c\u00f4ng!');
                } else {
                    alert('L\u1ed7i: ' + (res.message || 'Kh\u00f4ng th\u1ec3 c\u1eadp nh\u1eadt.'));
                }
            },
            error: function() {
                alert('M\u1ea1ng kh\u00f4ng \u1ed5n \u0111\u1ecbnh khi l\u01b0u d\u1eef li\u1ec7u.');
            }
        });
    }

    /** T?o nhanh H? so khï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ch hï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng CRM t?c thï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ */
    function instantCreateCustomer() {
        var channel = _selectedChannel;
        var recordId = _selectedContactId;

        if (!recordId || !channel) {
            alert('L\u1ed7i: Thi\u1ebfu ID ho\u1eb7c K\u00eanh kh\u00e1ch h\u00e0ng.');
            return;
        }

        // Ki?m tra xem dï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ cï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ Sï¿½fÂ¯ï¿½,Â¿ï¿½,Â½T chua
        var phoneEl = $('#leadPhone');
        var phone = phoneEl.length ? phoneEl.val().trim() : '';

        if (!phone || phone.length < 9) {
            phone = prompt('Kh\u00e1ch h\u00e0ng ch\u01b0a c\u00f3 S\u1ed1 \u0111i\u1ec7n tho\u1ea1i. Vui l\u00f2ng nh\u1eadp S\u1ed1 \u0111i\u1ec7n tho\u1ea1i li\u00ean h\u1ec7 \u0111\u1ec3 t\u1ea1o H\u1ed3 s\u01a1 KH:', '');
            if (phone === null) return; // Ngu?i dï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ng h?y
            phone = phone.trim();
            if (!phone || phone.length < 9) {
                alert('S\u1ed1 \u0111i\u1ec7n tho\u1ea1i kh\u00f4ng h\u1ee3p l\u1ec7 (y\u00eau c\u1ea7u \u00edt nh\u1ea5t 9 s\u1ed1)!');
                return;
            }
            if (phoneEl.length) {
                phoneEl.val(phone);
            }
        }

        if (!confirm('B\u1ea1n c\u00f3 ch\u1eafc ch\u1eafn mu\u1ed1n t\u1ea1o nhanh h\u1ed3 s\u01a1 CRM cho kh\u00e1ch h\u00e0ng n\u00e0y?')) {
            return;
        }

        $.ajax({
            url: _urls.index + '/instant-create-customer',
            type: 'POST',
            data: {
                channel: channel,
                id: recordId,
                phone: phone
            },
            success: function(res) {
                if (res.status === 'success') {
                    // T?i l?i khung chat d? hi?n th? liï¿½fÂ¯ï¿½,Â¿ï¿½,Â½n k?t CRM m?i
                    _loadChatContent(window.location.href, false);
                    alert(res.message || 'T\u1ea1o h\u1ed3 s\u01a1 KH th\u00e0nh c\u00f4ng!');
                } else {
                    alert('L\u1ed7i: ' + (res.message || 'Kh\u00f4ng th\u1ec3 t\u1ea1o h\u1ed3 s\u01a1.'));
                }
            },
            error: function() {
                alert('M\u1ea1ng kh\u00f4ng \u1ed5n \u0111\u1ecbnh khi g\u1eedi y\u00eau c\u1ea7u t\u1ea1o h\u1ed3 s\u01a1.');
            }
        });
    }

    // =========================================================
    // PUBLIC API ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½ ï¿½fÂ¯ï¿½,Â¿ï¿½,Â½u?c g?i t? view (onclick, oninit...)
    // =========================================================
    return {
        init:                  init,
        saveTags:              saveTags,
        createNewTag:          createNewTag,
        useQuickReply:         useQuickReply,
        saveLeadField:         saveLeadField,
        instantCreateCustomer: instantCreateCustomer,
        openCallModal:         openCallModal
    };

})();

$(document).on('click', '.js-open-attachment', function() {
    const url = $(this).data('url');
    if (url) window.open(url, '_blank', 'noopener,noreferrer');
});

$(document).on('click', '.js-instant-create-customer', function() {
    ChatApp.instantCreateCustomer();
});

$(document).on('click', '.js-create-new-tag', function() {
    ChatApp.createNewTag();
});

$(document).on('click', '.js-save-tags', function() {
    ChatApp.saveTags($(this).data('contact-id'));
});

$(document).on('click', '.js-save-lead-field', function() {
    ChatApp.saveLeadField($(this).data('field'));
});

$(document).on('click', '.js-use-quick-reply', function() {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = $(this).attr('data-content') || '';
    ChatApp.useQuickReply(textarea.value);
});

$(document).ready(function() {
    const configEl = document.querySelector('.chat-page-container[data-chat-config]');
    if (!configEl || typeof ChatApp === 'undefined') {
        return;
    }

    try {
        const config = JSON.parse(configEl.getAttribute('data-chat-config') || '{}');
        ChatApp.init(config);
    } catch (err) {
        console.error('Kh\u00f4ng th\u1ec3 kh\u1edfi t\u1ea1o ChatApp:', err);
    }
});
