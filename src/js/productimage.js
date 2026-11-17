/**
 * Picture of a pricelist item: turning, cropping and uploading.
 *
 * The crop area is drawn by dragging over the picture; it is sent as fractions of the picture,
 * so the server cuts the real picture whatever size the browser shows.
 */
(function () {
    'use strict';

    function init(root) {
        var stage = root.querySelector('.pimg-stage');
        var img = root.querySelector('.pimg-img');
        var box = root.querySelector('.pimg-crop');
        var status = root.querySelector('.pimg-status');
        var cropButton = root.querySelector('[data-pimg="crop"]');
        var aspectSelect = root.querySelector('.pimg-aspect');
        var buttons = root.querySelectorAll('button[data-pimg]');
        var area = null;
        var start = null;

        function say(text, kind) {
            status.className = 'pimg-status mt-2 small text-' + (kind || 'muted');
            status.textContent = text;
        }

        function busy(on) {
            buttons.forEach(function (button) {
                button.disabled = on || (button === cropButton && !area);
            });
        }

        function aspect() {
            var value = aspectSelect.value;

            if (value === 'free') {
                return null;
            }

            var parts = value.split(':');

            return parseFloat(parts[0]) / parseFloat(parts[1]);
        }

        function clear() {
            area = null;
            box.hidden = true;
            cropButton.disabled = true;
        }

        function draw() {
            if (!area) {
                return;
            }

            box.hidden = false;
            box.style.left = (area.x * 100) + '%';
            box.style.top = (area.y * 100) + '%';
            box.style.width = (area.w * 100) + '%';
            box.style.height = (area.h * 100) + '%';
            cropButton.disabled = area.w < 0.02 || area.h < 0.02;
        }

        function point(event) {
            var rect = img.getBoundingClientRect();

            return {
                x: Math.min(1, Math.max(0, (event.clientX - rect.left) / rect.width)),
                y: Math.min(1, Math.max(0, (event.clientY - rect.top) / rect.height)),
                ratio: rect.width / rect.height
            };
        }

        stage.addEventListener('pointerdown', function (event) {
            if (event.button !== 0 || !img.naturalWidth) {
                return;
            }

            event.preventDefault();
            stage.setPointerCapture(event.pointerId);
            start = point(event);
            area = {x: start.x, y: start.y, w: 0, h: 0};
            draw();
        });

        stage.addEventListener('pointermove', function (event) {
            if (!start) {
                return;
            }

            var now = point(event);
            var dx = now.x - start.x;
            var dy = now.y - start.y;
            var wanted = aspect(); // width / height of the area in pixels

            if (wanted !== null) {
                // dx and dy are fractions of different sides, compare them in pixels of the picture
                var pixelsW = Math.abs(dx) * now.ratio;
                var pixelsH = Math.abs(dy);

                if (pixelsW / wanted > pixelsH) {
                    pixelsW = pixelsH * wanted;
                } else {
                    pixelsH = pixelsW / wanted;
                }

                dx = Math.sign(dx || 1) * pixelsW / now.ratio;
                dy = Math.sign(dy || 1) * pixelsH;

                // keep the area inside of the picture
                var maxX = dx > 0 ? 1 - start.x : start.x;
                var maxY = dy > 0 ? 1 - start.y : start.y;
                var shrink = Math.min(1, maxX / Math.abs(dx || 1), maxY / Math.abs(dy || 1));

                dx *= shrink;
                dy *= shrink;
            }

            area = {
                x: Math.min(start.x, start.x + dx),
                y: Math.min(start.y, start.y + dy),
                w: Math.abs(dx),
                h: Math.abs(dy)
            };
            draw();
        });

        function release() {
            start = null;
        }

        stage.addEventListener('pointerup', release);
        stage.addEventListener('pointercancel', release);

        aspectSelect.addEventListener('change', clear);

        function send(data) {
            var form = new FormData();

            form.append('csrf_token', root.dataset.csrf);
            form.append('id', root.dataset.id);

            Object.keys(data).forEach(function (key) {
                form.append(key, data[key]);
            });

            busy(true);
            say(root.dataset.working);

            return fetch(root.dataset.endpoint, {method: 'POST', body: form, credentials: 'same-origin'})
                .then(function (response) {
                    return response.json().catch(function () {
                        return {ok: false, error: response.status + ' ' + response.statusText};
                    });
                })
                .then(function (result) {
                    if (!result.ok) {
                        throw new Error(result.error);
                    }

                    clear();
                    img.src = root.dataset.src + '&t=' + Date.now();
                    say(root.dataset.done, 'success');
                })
                .catch(function (error) {
                    say(error.message, 'danger');
                })
                .then(function () {
                    busy(false);
                });
        }

        root.addEventListener('click', function (event) {
            var button = event.target.closest('button[data-pimg]');

            if (!button || button.disabled) {
                return;
            }

            var action = button.dataset.pimg;

            if (action === 'rotate') {
                send({action: 'rotate', degrees: button.dataset.degrees});
            } else if (action === 'crop' && area) {
                send({action: 'crop', x: area.x, y: area.y, width: area.w, height: area.h});
            } else if (action === 'reset') {
                clear();
                say('');
            }
        });

        root.querySelector('.pimg-file').addEventListener('change', function (event) {
            var file = event.target.files[0];

            if (file) {
                send({action: 'upload', image: file}).then(function () {
                    event.target.value = '';
                });
            }
        });

        img.addEventListener('error', function () {
            root.classList.add('pimg-empty');
        });
        img.addEventListener('load', function () {
            root.classList.remove('pimg-empty');
        });
    }

    document.querySelectorAll('.pimg').forEach(init);
})();
