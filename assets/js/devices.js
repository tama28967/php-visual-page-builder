/**
 * Device Manager wiring — GrapesJS already provides the device switching
 * logic; this only wires the top bar buttons to it.
 */
function initDeviceButtons(editor) {
    var buttons = document.querySelectorAll('.device-btn');
    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var device = btn.getAttribute('data-device');
            editor.setDevice(device);
            buttons.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
        });
    });
}
