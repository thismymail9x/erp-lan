$(document).ready(function() {
    const zaloPageContainer = document.querySelector('.zalo-page-container');
    let selectedMid = zaloPageContainer ? zaloPageContainer.dataset.selectedZaloId : '';
    let lastMsgId = zaloPageContainer ? zaloPageContainer.dataset.lastMsgId : '0';
    
    // Cuộn xuống cuối tin nhắn
    function scrollToBottom() {
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }
    scrollToBottom();

    // 1. Câu trả lời nhanh
    window.useQuickReply = function(content) {
        $('.chat-input').val(content);
        $('#quickReplyModal').fadeOut();
        $('.chat-input').focus();
    }

    function searchQuickReplies(query) {
        const resultsContainer = $('#qrSearchResults');
        resultsContainer.html('<div style="text-align: center; padding: 20px; color: #94a3b8;"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>');

        $.ajax({
            url: baseUrl + 'zalo/quick-replies/search',
            type: 'GET',
            data: { q: query },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success' && res.data && res.data.length > 0) {
                    let html = '';
                    res.data.forEach(function(qr) {
                        const escapedContent = qr.content.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
                        html += `
                            <div class="quick-reply-item" onclick="window.useQuickReply('${escapedContent}')" style="padding: 10px 14px; border-radius: 8px; cursor: pointer; border: 1px solid #e2e8f0; margin-bottom: 8px; transition: all 0.2s;">
                                <div style="font-weight: 600; font-size: 13px; color: #0ea5e9;">${escapeHtml(qr.title)}</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(qr.content)}</div>
                            </div>
                        `;
                    });
                    resultsContainer.html(html);
                } else {
                    resultsContainer.html('<p style="text-align: center; color: #94a3b8; font-size: 13px; padding: 20px;">Không tìm thấy câu trả lời nào.</p>');
                }
            },
            error: function() {
                resultsContainer.html('<p style="text-align: center; color: #ef4444; font-size: 13px; padding: 20px;">Lỗi kết nối tải câu trả lời.</p>');
            }
        });
    }

    $(document).on('click', '.fa-bolt', function() {
        $('#quickReplyModal').css('display', 'flex').hide().fadeIn();
        $('#qrSearchInput').val('');
        searchQuickReplies('');
        setTimeout(function() {
            $('#qrSearchInput').focus();
        }, 100);
    });

    let qrSearchTimeout = null;
    $(document).on('input', '#qrSearchInput', function() {
        const query = $(this).val().trim();
        clearTimeout(qrSearchTimeout);
        qrSearchTimeout = setTimeout(function() {
            searchQuickReplies(query);
        }, 300);
    });

    // 2. Gắn nhãn (Tagging) - Dùng change event trên checkbox để tránh double-toggle với label
    // (Nếu bind click trên .tag-option bên trong <label>, browser sẽ toggle checkbox 2 lần: 1 lần bởi JS, 1 lần bởi <label>)
    $(document).on('change', '.tag-checkbox', function() {
        const span = $(this).siblings('.tag-option');
        if ($(this).is(':checked')) {
            span.css({'background': '#0ea5e9', 'color': '#fff', 'border-color': '#0ea5e9'});
        } else {
            span.css({'background': 'transparent', 'color': 'inherit', 'border-color': '#cbd5e1'});
        }
    });

    // Khởi tạo style cho các checkbox đã được check từ server
    function initTagStyles() {
        $('.tag-checkbox').each(function() {
            const span = $(this).siblings('.tag-option');
            if ($(this).is(':checked')) {
                span.css({'background': '#0ea5e9', 'color': '#fff', 'border-color': '#0ea5e9'});
            } else {
                span.css({'background': 'transparent', 'color': 'inherit', 'border-color': '#cbd5e1'});
            }
        });
    }
    initTagStyles();

    window.saveTags = function(followerId) {
        const selectedTags = [];
        $('.tag-checkbox:checked').each(function() {
            selectedTags.push($(this).val());
        });

        $.post(baseUrl + 'zalo/update-tags', {
            follower_id: followerId,
            tags: selectedTags
        }, function(res) {
            if (res.status === 'success') {
                // 1. Cập nhật tags trong panel hồ sơ (bên phải)
                let panelHtml = '';
                if (res.tags && res.tags.length > 0) {
                    res.tags.forEach(t => { panelHtml += `<span class="tag-badge">#${t}</span> `; });
                } else {
                    panelHtml = '<span class="tag-badge" style="background: #e2e8f0; color: #64748b;">Chưa có nhãn</span>';
                }
                $('#currentTags').html(panelHtml);

                // 2. Cập nhật tags trong chat header (vùng thấy rõ nhất)
                const headerTagsContainer = $('#chatHeaderTags');
                if (headerTagsContainer.length) {
                    let headerHtml = '';
                    if (res.tags && res.tags.length > 0) {
                        res.tags.slice(0, 3).forEach(t => {
                            headerHtml += `<span class="tag-badge" style="font-size: 11px; padding: 2px 8px;">#${t}</span>`;
                        });
                        if (res.tags.length > 3) {
                            headerHtml += `<span style="font-size: 11px; color: #94a3b8;">+${res.tags.length - 3}</span>`;
                        }
                    }
                    headerTagsContainer.html(headerHtml);
                }

                // 3. Cập nhật tags trong sidebar list
                const sidebarItem = $(`.conversation-link[data-mid="${selectedMid}"] .conversation-meta`);
                if (sidebarItem.length) {
                    sidebarItem.find('.conv-tag-badge, .extra-tag-count').remove();
                    if (res.tags && res.tags.length > 0) {
                        res.tags.slice(0, 2).forEach(t => {
                            sidebarItem.append(`<span class="conv-tag-badge">#${t}</span>`);
                        });
                        if (res.tags.length > 2) {
                            sidebarItem.append(`<span class="extra-tag-count" style="font-size: 10px; color: #94a3b8;">+${res.tags.length - 2}</span>`);
                        }
                    }
                }

                $('#tagEditModal').hide();
                alert('Đã cập nhật nhãn thành công!');
            } else {
                alert('Lỗi: ' + res.message);
            }
        });
    }

    // Tạo nhãn mới nhanh từ modal gắn nhãn
    window.createNewTag = function() {
        const tagName = $('#newTagInput').val().trim();
        if (!tagName) {
            alert('Vui lòng nhập tên nhãn.');
            return;
        }
        
        $.post(baseUrl + 'zalo/create-tag', {
            name: tagName
        }, function(res) {
            if (res.status === 'success') {
                // Thêm checkbox mới vào danh sách
                const tagHtml = `
                    <label style="cursor: pointer; margin: 0;">
                        <input type="checkbox" class="tag-checkbox" value="${res.tag.name}" checked style="display: none;">
                        <span class="tag-option" style="padding: 4px 12px; border-radius: 20px; border: 1px solid #0ea5e9; font-size: 12px; transition: all 0.2s; display: inline-block; background: #0ea5e9; color: #fff;">
                            #${res.tag.name}
                        </span>
                    </label>
                `;
                // Thêm vào container danh sách tags (dùng id ổn định hơn)
                const container = $('#tagCheckboxList');
                container.find('.no-tags-msg').remove();
                container.append(tagHtml);
                
                $('#newTagInput').val('');
                
                // Cập nhật bộ lọc sidebar nếu có
                const filterSelect = $('select[name="filter_tag"]');
                filterSelect.append(`<option value="${res.tag.name}">#${res.tag.name}</option>`);
            } else {
                alert('Lỗi: ' + res.message);
            }
        });
    }

    // 3. Điều hướng AJAX cho hội thoại và bộ lọc
    function loadChatContent(url, pushState = true) {
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            beforeSend: function() {
                // Hiệu ứng loading nhẹ
                $('#zaloSidebar').css('opacity', '0.5');
                $('#zaloMainChat').css('opacity', '0.5');
            },
            success: function(res) {
                $('#zaloSidebar').html(res.sidebar_html).css('opacity', '1');
                $('#zaloMainChat').html(res.chat_area_html).css('opacity', '1');
                
                selectedMid = res.selectedZaloId;
                lastMsgId = res.lastMsgId;
                
                if (pushState) {
                    history.pushState({url: url}, res.title, url);
                }
                document.title = res.title;
                
                scrollToBottom();
                
                // Reset infinite scroll state khi đổi khách
                hasMoreMessages = true;
                isLoadingMore = false;
                
                // Khởi tạo lại style cho tag checkboxes sau khi load AJAX
                initTagStyles();
            },
            error: function() {
                alert('Không thể tải nội dung. Vui lòng thử lại.');
                $('#zaloSidebar, #zaloMainChat').css('opacity', '1');
            }
        });
    }

    // Intercept click vào hội thoại
    $(document).on('click', '.conversation-link', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        loadChatContent(url);
    });

    // Intercept submit form filter
    $(document).on('submit', '#filterForm', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const url = baseUrl + baseUrl + 'zalo?' + formData;
        loadChatContent(url);
    });

    // Intercept select change (cả .filter-select lẫn .zalo-filter-select)
    $(document).on('change', '.filter-select, .zalo-filter-select', function() {
        $('#filterForm').submit();
    });

    // Handle back button
    window.onpopstate = function(event) {
        if (event.state && event.state.url) {
            loadChatContent(event.state.url, false);
        } else {
            loadChatContent(window.location.href, false);
        }
    };

    // Polling lấy tin nhắn mới mỗi 3 giây (vẫn giữ polling nhưng chỉ update data)
    setInterval(function() {
        if (!selectedMid) return; // Chỉ poll khi đang chọn 1 khách
        
        const urlParams = new URLSearchParams(window.location.search);
        $.ajax({
            url: baseUrl + 'zalo/ajax-chat',
            type: 'GET',
            data: { 
                mid: selectedMid,
                last_msg_id: lastMsgId,
                search: urlParams.get('search') || '',
                filter_staff: urlParams.get('filter_staff') || '',
                filter_tag: urlParams.get('filter_tag') || ''
            },
            success: function(response) {
                // 1. Cập nhật tin nhắn mới
                if (response.new_messages && response.new_messages.length > 0) {
                    response.new_messages.forEach(function(msg) {
                        lastMsgId = msg.id;
                        let isReceived = (msg.sender_type === 'user');
                        let html = `
                            <div class="message-bubble ${isReceived ? 'received' : 'sent'}" data-msg-id="${msg.id}">
                                <div class="message-content">
                                    ${escapeHtml(msg.message_text)}
                                    ${renderAttachments(msg)}
                                </div>
                                <div class="message-time">${formatDate(msg.created_at)}</div>
                            </div>
                        `;
                        $('#chatMessages').append(html);
                    });
                    scrollToBottom();
                }

                // 2. Cập nhật preview ở sidebar (chỉ cập nhật nội dung, không nạp lại toàn bộ HTML nếu không cần thiết)
                // Tuy nhiên để đơn giản và đồng bộ, ta có thể để nó cập nhật các badge/preview
                if (response.followers && response.followers.length > 0) {
                    response.followers.forEach(function(f) {
                        const item = $(`.conversation-link[data-mid="${f.zalo_id}"]`);
                        if (item.length) {
                            item.find('.conversation-preview').text(f.last_message);
                            item.find('.conversation-time').text(f.last_time);
                            const badge = item.find('.unread-badge');
                            if (f.unread_count > 0) {
                                if (badge.length) badge.text(f.unread_count);
                                else item.find('.conversation-time').after(`<span class="unread-badge">${f.unread_count}</span>`);
                            } else {
                                badge.remove();
                            }
                        }
                    });
                }
            }
        });
    }, 3000);

    // 4. Tải lên Media (Hình ảnh/Tệp tin)
    window.handleMediaUpload = function(input) {
        if (!input.files || !input.files[0] || !selectedMid) return;
        
        const file = input.files[0];
        const isImage = file.type.startsWith('image/');
        const maxSize = isImage ? 1 * 1024 * 1024 : 5 * 1024 * 1024; // 1MB for image, 5MB for file

        if (file.size > maxSize) {
            alert(`Tệp quá lớn. Giới hạn: ${isImage ? '1MB' : '5MB'}. Vui lòng nén hoặc giảm dung lượng trước khi gửi.`);
            $(input).val('');
            return;
        }

        let formData = new FormData();
        formData.append('mid', selectedMid);
        formData.append('media', file);

        // Hiển thị loading
        $('.chat-input-area').css('opacity', '0.5');

        $.ajax({
            url: baseUrl + 'zalo/upload-media',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.status === 'success') {
                    // Cập nhật chat qua polling
                    $(input).val('');
                } else {
                    alert('Lỗi: ' + res.message);
                }
                $('.chat-input-area').css('opacity', '1');
            },
            error: function() {
                alert('Mạng không ổn định khi tải lên.');
                $('.chat-input-area').css('opacity', '1');
            }
        });
    }

    // 5. Ghi nhận cuộc gọi
    window.submitCallLog = function() {
        const notes = $('#callNotes').val().trim();
        if (!selectedMid) return;

        $.post(baseUrl + 'zalo/log-call', {
            mid: selectedMid,
            notes: notes
        }, function(res) {
            if (res.status === 'success') {
                $('#callNotes').val('');
                $('#callLogModal').fadeOut();
                alert('Đã ghi nhận lịch sử cuộc gọi!');
            } else {
                alert('Lỗi: ' + res.message);
            }
        });
    }

    // 6. Đồng bộ lại profile khách hàng
    window.syncZaloProfile = function(mid) {
        if (!mid) return;
        
        const btn = $('.fa-sync-alt');
        btn.addClass('fa-spin');
        
        $.post(baseUrl + 'zalo/sync-profile', {
            mid: mid
        }, function(res) {
            if (res.status === 'success') {
                // Cập nhật tên và avatar trong header
                $('.chat-header .zalo-avatar').attr('src', res.data.avatar_url);
                $('.chat-header .content-title, .chat-header div:first-child div:first-child').text(res.data.display_name);
                alert(res.message);
                // Load lại content để đồng bộ toàn bộ
                loadChatContent(window.location.href, false);
            } else {
                alert(res.message);
            }
            btn.removeClass('fa-spin');
        });
    }

    // 7. Infinite Scroll logic cho tin nhắn cũ
    let isLoadingMore = false;
    let hasMoreMessages = true;
    
    $(document).on('scroll', '#chatMessages', function() {
        if ($(this).scrollTop() === 0 && !isLoadingMore && hasMoreMessages) {
            loadMoreMessages();
        }
    });

    function loadMoreMessages() {
        if (!selectedMid) return;
        
        const firstMsg = $('#chatMessages .message-bubble').first();
        if (!firstMsg.length) return;
        
        const firstMsgId = firstMsg.data('msg-id');
        if (!firstMsgId) return;

        isLoadingMore = true;
        const oldHeight = $('#chatMessages')[0].scrollHeight;

        $.ajax({
            url: baseUrl + 'zalo/load-more-messages',
            type: 'GET',
            data: { 
                mid: selectedMid,
                first_msg_id: firstMsgId
            },
            success: function(res) {
                if (res.status === 'success' && res.messages && res.messages.length > 0) {
                    let html = '';
                    res.messages.forEach(function(msg) {
                        let isReceived = (msg.sender_type === 'user');
                        html += `
                            <div class="message-bubble ${isReceived ? 'received' : 'sent'}" data-msg-id="${msg.id}">
                                <div class="message-content">
                                    ${escapeHtml(msg.message_text)}
                                    ${renderAttachments(msg)}
                                </div>
                                <div class="message-time">${formatDate(msg.created_at)}</div>
                            </div>
                        `;
                    });
                    $('#chatMessages').prepend(html);
                    
                    // Giữ vị trí scroll để không bị nhảy xuống dưới
                    const newHeight = $('#chatMessages')[0].scrollHeight;
                    $('#chatMessages').scrollTop(newHeight - oldHeight);
                    
                    // Nếu lấy về ít hơn 10 tin nhắn thì nghĩa là đã hết tin nhắn cũ
                    if (res.messages.length < 10) hasMoreMessages = false;
                } else {
                    hasMoreMessages = false;
                }
                isLoadingMore = false;
            },
            error: function() {
                isLoadingMore = false;
            }
        });
    }

    // Sử dụng Event Delegation cho các phần tử trong khung chat load bằng AJAX
    $(document).on('keypress', '.chat-input', function(e) {
        if (e.which == 13) {
            sendMessage();
        }
    });

    $(document).on('click', '.btn-send', function() {
        sendMessage();
    });

    function sendMessage() {
        let message = $('.chat-input').val().trim();
        if (!message || !selectedMid) return;

        $('.btn-send').prop('disabled', true);
        
        $.ajax({
            url: baseUrl + 'zalo/send-message',
            type: 'POST',
            data: {
                mid: selectedMid,
                message: message
            },
            success: function(response) {
                if (response.status === 'success') {
                    $('.chat-input').val('');
                    // Tin nhắn sẽ được cập nhật qua polling hoặc thêm trực tiếp
                } else {
                    alert('Lỗi: ' + response.message);
                }
                $('.btn-send').prop('disabled', false);
            },
            error: function() {
                alert('Mạng không ổn định.');
                $('.btn-send').prop('disabled', false);
            }
        });
    }

    // Xử lý gán nhân sự (Sử dụng Event Delegation để hoạt động ngay cả khi load lại bằng AJAX)
    $(document).on('change', '#staffAssignment', function() {
        const followerId = $(this).data('follower-id');
        const staffId = $(this).val();
        
        $.post(baseUrl + 'zalo/assign-staff', {
            follower_id: followerId,
            staff_id: staffId
        }, function(res) {
            if (res.status === 'success') {
                alert('Đã cập nhật nhân sự phụ trách thành công!');
            } else {
                alert('Lỗi: ' + res.message);
            }
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function formatDate(dateStr) {
        let d = new Date(dateStr);
        let h = String(d.getHours()).padStart(2, '0');
        let m = String(d.getMinutes()).padStart(2, '0');
        let day = String(d.getDate()).padStart(2, '0');
        let month = String(d.getMonth() + 1).padStart(2, '0');
        let year = d.getFullYear();
        return `${h}:${m} ${day}/${month}/${year}`;
    }

    function renderAttachments(msg) {
        if (!msg.attachments) return '';
        let attachments = JSON.parse(msg.attachments);
        if (!attachments || attachments.length === 0) return '';

        let html = '<div class="message-attachments" style="margin-top: 8px; display: flex; flex-direction: column; gap: 8px;">';
        attachments.forEach(attach => {
            if (attach.type === 'image') {
                let imageUrl = (attach.payload && attach.payload.url) ? attach.payload.url : (attach.url || '');
                // Nếu URL từ Zalo chưa có, thử dùng ảnh tạm từ server ERP
                if (!imageUrl && attach.payload && attach.payload.local_file) {
                    imageUrl = baseUrl + baseUrl + 'zalo/view-temp/' + attach.payload.local_file;
                }
                
                if (imageUrl) {
                    html += `<div class="attach-image"><img src="${imageUrl}" style="max-width: 200px; border-radius: 8px; cursor: pointer;" onclick="window.open('${imageUrl}')"></div>`;
                }
            } else if (attach.type === 'file' || attach.type === 'video') {
                let name = attach.payload.name || (attach.type === 'video' ? 'Video' : 'File');
                let size = attach.payload.size || 0;
                let token = attach.payload.token || '';
                let sizeStr = size > 1048576 ? Math.round(size/1048576 * 100) / 100 + ' MB' : Math.round(size/1024 * 100) / 100 + ' KB';
                
                html += `
                    <div class="attach-file" style="background: rgba(0,0,0,0.05); padding: 8px; border-radius: 6px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas ${attach.type === 'video' ? 'fa-video' : 'fa-file-download'}" style="font-size: 20px; color: ${attach.type === 'video' ? '#ef4444' : '#3b82f6'};"></i>
                        <div style="flex: 1; overflow: hidden;">
                            <div style="font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${escapeHtml(name)}</div>
                            <div style="font-size: 11px; color: #64748b;">${sizeStr}</div>
                        </div>
                        <a href="${baseUrl}zalo/download-attachment?msg_id=${msg.id}&token=${encodeURIComponent(token)}&name=${encodeURIComponent(name)}&size=${size}" class="btn-download" style="color: #3b82f6;"><i class="fas fa-cloud-download-alt"></i></a>
                    </div>
                `;
            } else if (attach.type === 'sticker') {
                let stickerUrl = attach.payload.url || '';
                if (stickerUrl) {
                    html += `<div class="attach-sticker"><img src="${stickerUrl}" style="width: 100px;"></div>`;
                }
            }
        });
        html += '</div>';
        return html;
    }

    window.syncHistory = function(mid) {
        if (!mid) return;
        
        const btn = $('.btn-sync-history');
        const icon = btn.find('i');
        icon.addClass('fa-spin');
        btn.prop('disabled', true);
        
        $.post(baseUrl + 'zalo/sync', {
            mid: mid
        }, function(res) {
            if (res.status === 'success') {
                alert(res.message);
                // Tải lại nội dung chat qua AJAX để cập nhật hội thoại mới
                loadChatContent(window.location.href, false);
            } else {
                alert('Đồng bộ thất bại: ' + res.message);
            }
            icon.removeClass('fa-spin');
            btn.prop('disabled', false);
        });
    }
});
