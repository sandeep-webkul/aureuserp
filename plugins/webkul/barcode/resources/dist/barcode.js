document.addEventListener('alpine:init', () => {
    Alpine.data('barcodeScanner', (property = 'barcode', action = 'scan') => ({
        active: false,
        processing: false,
        scanner: null,
        scannerError: '',
        property,
        action,
        confirmPending: null,
        confirmLabel: '',
        confirmMode: null,
        backorderMoveLines: [],
        actionMenuOpen: false,

        requestAction(key, label) {
            this.actionMenuOpen = false;
            this.confirmPending = key;
            this.confirmLabel = label;
            this.confirmMode = 'simple';
            this.backorderMoveLines = [];
        },

        requestValidate(label, backorderMoveLines, hasAnyCounted, shouldAskBackorder) {
            this.actionMenuOpen = false;
            this.confirmPending = 'validate';
            this.confirmLabel = label;
            this.backorderMoveLines = backorderMoveLines;
            this.confirmMode = shouldAskBackorder && hasAnyCounted && backorderMoveLines.length > 0
                ? 'backorder'
                : 'simple';
        },

        cancelAction() {
            this.confirmPending = null;
            this.confirmLabel = '';
            this.confirmMode = null;
            this.backorderMoveLines = [];
            this.actionMenuOpen = false;
        },

        toggleActionMenu() {
            this.actionMenuOpen = !this.actionMenuOpen;
        },

        closeActionMenu() {
            this.actionMenuOpen = false;
        },

        renderScannerError(message) {
            this.scannerError = message;
        },

        async toggle($wire) {
            if (this.active) {
                await this.stop();

                return;
            }

            await this.start($wire);
        },

        async start($wire) {
            this.active = true;
            this.scannerError = '';

            if (! window.Html5Qrcode) {
                this.renderScannerError('Scanner library failed to load.');

                return;
            }

            this.scanner = new Html5Qrcode('barcode-reader');

            try {
                await this.scanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 260, height: 180 } },
                    async (decodedText) => {
                        if (this.processing) {
                            return;
                        }

                        this.processing = true;
                        await this.stop();
                        $wire.set(this.property, decodedText);
                        await $wire[this.action]();
                        this.processing = false;
                    },
                );
            } catch (error) {
                console.error('Barcode scanner failed to start.', error);
                this.scanner = null;
                this.renderScannerError('Unable to access the camera. Check camera permission and try again.');
                window.BarcodeNative?.toast?.('Unable to access the camera.', 'short');
            }
        },

        async stop() {
            this.active = false;

            if (this.scanner) {
                await this.scanner.stop();
                this.scanner.clear();
                this.scanner = null;
            }

            const reader = document.getElementById('barcode-reader');

            if (reader) {
                reader.innerHTML = '';
            }
        },
    }));
});

let lastLocatedMoveLineKey = null;

window.addEventListener('barcode-native-feedback', (event) => {
    const detail = event.detail ?? {};

    if (detail.vibrate) {
        window.BarcodeNative?.vibrate?.();
    }

    if (detail.message) {
        window.BarcodeNative?.toast?.(detail.message, detail.duration ?? 'short');
    }
});

window.addEventListener('barcode-move-line-located', (event) => {
    const moveLineKey = `${event.detail.moveLineId}:${event.detail.scannedAt}`;

    if (moveLineKey === lastLocatedMoveLineKey) {
        return;
    }

    lastLocatedMoveLineKey = moveLineKey;

    const moveLine = document.getElementById(`line-${event.detail.moveLineId}`);

    if (! moveLine) {
        return;
    }

    moveLine.scrollIntoView({ behavior: 'smooth', block: 'center' });

    const input = moveLine.querySelector('input[type="number"]');

    if (input) {
        setTimeout(() => {
            input.focus();
            input.select();
        }, 250);
    }
});
