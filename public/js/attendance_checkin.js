/**
 * L.A.N ERP - cháº¥m cÃ´ng Ä‘a phÆ°Æ¡ng thá»©c: Mobile, LAN vÃ  mÃ¡y vÄƒn phÃ²ng Ä‘Ã£ á»§y quyá»n.
 */
(function () {
    const timeElement = document.getElementById('current-time');
    const dateElement = document.querySelector('.clock-display-date');
    const days = ['Ch\u1ee7 Nh\u1eadt', 'Th\u1ee9 Hai', 'Th\u1ee9 Ba', 'Th\u1ee9 T\u01b0', 'Th\u1ee9 N\u0103m', 'Th\u1ee9 S\u00e1u', 'Th\u1ee9 B\u1ea3y'];

    function updateClock() {
        if (!timeElement) return;
        const now = new Date();
        timeElement.textContent = now.toLocaleTimeString('en-GB', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });

        if (dateElement && dateElement.textContent.trim() === '') {
            dateElement.textContent = days[now.getDay()] + ', ' + now.toLocaleDateString('vi-VN');
        }
    }

    setInterval(updateClock, 1000);
    document.addEventListener('DOMContentLoaded', updateClock);
})();

document.addEventListener('DOMContentLoaded', function () {
    const page = document.querySelector('.attendance-page-container');
    if (!page) return;

    const config = page.dataset;
    const isMobile = config.isMobile === '1';
    const isAdmin = config.isAdmin === '1';

    const statusBanner = document.getElementById('attendance-status');
    const video = document.getElementById('video-preview');
    const canvas = document.getElementById('photo-canvas');
    const btnInit = document.getElementById('btn-init');
    const btnSnap = document.getElementById('btn-snap');
    const btnSubmit = document.getElementById('btn-submit');
    const btnLanSubmit = document.getElementById('btn-lan-submit');
    const btnTokenSubmit = document.getElementById('btn-token-submit');
    const capturedContainer = document.getElementById('captured-container');
    const cameraArea = document.getElementById('camera-area');
    const capturedPhoto = document.getElementById('captured-photo');
    const placeholder = document.getElementById('photo-placeholder');
    const note = document.getElementById('note');

    let officeToken = localStorage.getItem('office_security_token');
    if (!officeToken) {
        const match = document.cookie.match(new RegExp('(^| )office_security_token=([^;]+)'));
        if (match) {
            officeToken = match[2];
            localStorage.setItem('office_security_token', officeToken);
        }
    }

    if (officeToken) {
        const date = new Date();
        date.setTime(date.getTime() + (3650 * 24 * 60 * 60 * 1000));
        document.cookie = `office_security_token=${officeToken}; expires=${date.toUTCString()}; path=/; SameSite=Lax`;
    }

    if (officeToken && !isMobile) {
        const tokenBox = document.getElementById('office-pc-status');
        const pcArea = document.getElementById('pc-attendance-area');
        if (tokenBox) tokenBox.classList.remove('is-hidden');
        if (pcArea) pcArea.classList.add('is-hidden');
    }

    let stream = null;
    let geoLocation = null;
    let photoBlob = null;
    let isSubmitting = false;

    async function checkStatus() {
        if (!statusBanner || !config.urlStatus) return;

        try {
            const res = await fetch(config.urlStatus);
            const result = await res.json();
            if (result.code === 0) {
                updateUI(result.data);
            }
        } catch (e) {
            console.error('L\u1ed7i check status:', e);
        }
    }

    function updateUI(data) {
        if (!statusBanner) return;

        const actionBtns = [btnSubmit, btnLanSubmit, btnTokenSubmit].filter(Boolean);

        if (data.status === 'NOT_CHECKED_IN') {
            statusBanner.className = 'status-indicator-banner status-banner-not-in';
            statusBanner.textContent = 'B\u1ea1n ch\u01b0a \u0111i\u1ec3m danh h\u00f4m nay. H\u00e3y b\u1eaft \u0111\u1ea7u Check-in.';
            actionBtns.forEach(function (button) {
                button.innerHTML = '<i class="fas fa-sign-in-alt"></i> X\u00e1c nh\u1eadn CHECK-IN';
            });
        } else if (data.status === 'CHECKED_IN') {
            statusBanner.className = 'status-indicator-banner status-banner-in';
            statusBanner.textContent = '\u0110\u00e3 v\u00e0o l\u00fac ' + data.check_in_time + '. S\u1eb5n s\u00e0ng CHECK-OUT.';
            actionBtns.forEach(function (button) {
                button.innerHTML = '<i class="fas fa-sign-out-alt"></i> X\u00e1c nh\u1eadn CHECK-OUT';
                button.classList.replace('btn-blue-apple', 'btn-secondary-apple');
                button.classList.add('btn-checkout-state');
            });
        } else if (data.status === 'CHECKED_OUT') {
            statusBanner.className = 'status-indicator-banner status-banner-done';
            statusBanner.textContent = '\u0110\u00e3 ho\u00e0n th\u00e0nh c\u00f4ng vi\u1ec7c h\u00f4m nay. Ngh\u1ec9 ng\u01a1i nh\u00e9!';
            actionBtns.forEach(function (button) {
                button.disabled = true;
                button.textContent = 'HO\u00c0N T\u1ea4T';
            });
            if (btnInit) btnInit.disabled = true;
        }
    }

    async function startAttendance() {
        if (!btnInit || btnInit.disabled || !statusBanner) return;

        btnInit.disabled = true;
        statusBanner.textContent = '\u0110ang kh\u1edfi \u0111\u1ed9ng Camera & \u0110\u1ecbnh v\u1ecb...';

        navigator.geolocation.getCurrentPosition(function (pos) {
            geoLocation = pos;
            navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            }).then(function (mediaStream) {
                stream = mediaStream;
                video.srcObject = mediaStream;
                video.setAttribute('playsinline', true);
                video.muted = true;

                video.play().then(function () {
                    video.classList.add('is-active');
                    if (cameraArea) cameraArea.classList.remove('is-hidden');
                    if (placeholder) placeholder.classList.add('is-hidden');
                    if (btnSnap) btnSnap.disabled = false;
                    statusBanner.textContent = 'S\u1eb5n s\u00e0ng! Vui l\u00f2ng ch\u1ee5p \u1ea3nh khu\u00f4n m\u1eb7t.';
                }).catch(function (e) {
                    console.error('L\u1ed7i play video:', e);
                    statusBanner.textContent = 'L\u1ed7i hi\u1ec3n th\u1ecb: H\u00e3y th\u1eed nh\u1ea5n "B\u1eaft \u0111\u1ea7u" l\u1ea1i.';
                });
            }).catch(function (e) {
                console.error('L\u1ed7i getUserMedia:', e);
                statusBanner.className = 'status-indicator-banner status-banner-error';
                statusBanner.textContent = 'L\u1ed7i Camera: Kh\u00f4ng th\u1ec3 truy c\u1eadp m\u00e1y \u1ea3nh.';
                btnInit.disabled = false;
                if (cameraArea) cameraArea.classList.add('is-hidden');
            });
        }, function () {
            statusBanner.className = 'status-indicator-banner status-banner-error';
            statusBanner.textContent = 'L\u1ed7i GPS: Vui l\u00f2ng b\u1eadt \u0111\u1ecbnh v\u1ecb v\u00e0 c\u1ea5p quy\u1ec1n cho tr\u00ecnh duy\u1ec7t.';
            btnInit.disabled = false;
            if (cameraArea) cameraArea.classList.add('is-hidden');
        });
    }

    if (isMobile && btnInit) {
        btnInit.onclick = startAttendance;
        setTimeout(function () {
            if (!btnInit.disabled) startAttendance();
        }, 500);
    }

    if (isMobile && btnSnap) {
        btnSnap.onclick = function () {
            if (video.videoWidth === 0 || video.videoHeight === 0 || video.readyState < 2) {
                alert('Camera \u0111ang kh\u1edfi \u0111\u1ed9ng, vui l\u00f2ng \u0111\u1ee3i 1 gi\u00e2y r\u1ed3i th\u1eed l\u1ea1i.');
                return;
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

            canvas.toBlob(function (blob) {
                if (!blob || blob.size === 0) {
                    alert('L\u1ed7i: Ch\u1ee5p \u1ea3nh kh\u00f4ng th\u00e0nh c\u00f4ng. Vui l\u00f2ng th\u1eed l\u1ea1i.');
                    return;
                }

                photoBlob = blob;
                capturedPhoto.src = URL.createObjectURL(blob);
                capturedContainer.classList.remove('is-hidden');
                capturedContainer.classList.add('is-active');
                video.classList.remove('is-active');
                btnSnap.disabled = true;
                btnSubmit.disabled = false;

                if (stream) stream.getTracks().forEach(function (track) {
                    track.stop();
                });
            }, 'image/jpeg', 0.8);
        };
    }

    if (isMobile && btnSubmit) {
        btnSubmit.onclick = function () {
            if (!geoLocation || !photoBlob) {
                alert('Vui l\u00f2ng b\u1eadt \u0111\u1ecbnh v\u1ecb v\u00e0 ch\u1ee5p \u1ea3nh tr\u01b0\u1edbc khi x\u00e1c nh\u1eadn.');
                return;
            }

            const fd = new FormData();
            fd.append('latitude', geoLocation.coords.latitude);
            fd.append('longitude', geoLocation.coords.longitude);
            fd.append('note', note ? note.value : '');
            fd.append('photo', photoBlob, 'att.jpg');
            submitData(fd, btnSubmit);
        };
    }

    if (btnLanSubmit) {
        btnLanSubmit.onclick = function () {
            const fd = new FormData();
            fd.append('note', note ? note.value : '');
            submitData(fd, btnLanSubmit);
        };
    }

    if (btnTokenSubmit) {
        btnTokenSubmit.onclick = function () {
            const fd = new FormData();
            fd.append('note', note ? note.value : '');
            fd.append('officeToken', officeToken);
            submitData(fd, btnTokenSubmit);
        };
    }

    const btnAuth = document.getElementById('btn-authorize-pc');
    if (btnAuth) {
        btnAuth.onclick = async function () {
            if (!confirm('X\u00e1c th\u1ef1c m\u00e1y t\u00ednh n\u00e0y l\u00e0 m\u00e1y v\u0103n ph\u00f2ng d\u00f9ng \u0111\u1ec3 ch\u1ea5m c\u00f4ng? Quy\u1ec1n n\u00e0y s\u1ebd \u0111\u01b0\u1ee3c duy tr\u00ec l\u00e2u d\u00e0i tr\u00ean m\u00e1y t\u00ednh n\u00e0y.')) {
                return;
            }

            const res = await fetch(config.urlOfficeToken);
            const result = await res.json();
            if (result.code === 0) {
                localStorage.setItem('office_security_token', result.token);

                const date = new Date();
                date.setTime(date.getTime() + (3650 * 24 * 60 * 60 * 1000));
                document.cookie = `office_security_token=${result.token}; expires=${date.toUTCString()}; path=/; SameSite=Lax`;

                document.getElementById('auth-success-msg').classList.remove('is-hidden');
                btnAuth.classList.add('is-hidden');
                setTimeout(function () {
                    location.reload();
                }, 1500);
            } else {
                alert('L\u1ed7i x\u00e1c th\u1ef1c: ' + result.error);
            }
        };
    }

    async function submitData(formData, btnElem) {
        if (isSubmitting || !config.urlSubmit) return;

        isSubmitting = true;
        const originalHTML = btnElem.innerHTML;
        btnElem.disabled = true;
        btnElem.textContent = '\u0110ang x\u1eed l\u00fd...';

        try {
            const res = await fetch(config.urlSubmit, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await res.json();
            if (result.code === 0) {
                alert(result.message);
                location.reload();
            } else {
                alert('Kh\u00f4ng th\u00e0nh c\u00f4ng: ' + result.error);
                btnElem.disabled = false;
                btnElem.innerHTML = originalHTML;
                isSubmitting = false;
            }
        } catch (e) {
            alert('L\u1ed7i k\u1ebft n\u1ed1i m\u00e1y ch\u1ee7.');
            btnElem.disabled = false;
            btnElem.innerHTML = originalHTML;
            isSubmitting = false;
        }
    }

    if (!isAdmin) {
        checkStatus();
    }
});