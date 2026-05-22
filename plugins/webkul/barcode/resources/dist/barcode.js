document.addEventListener('alpine:init', () => {
    Alpine.data('barcodeScanner', (property = 'barcode', action = 'scan') => ({
        active: false,
        processing: false,
        scanner: null,
        property,
        action,
        confirmPending: null,
        confirmLabel: '',

        requestAction(key, label) {
            this.confirmPending = key;
            this.confirmLabel = label;
        },

        cancelAction() {
            this.confirmPending = null;
            this.confirmLabel = '';
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

            if (! window.Html5Qrcode) {
                return;
            }

            this.scanner = new Html5Qrcode('barcode-reader');

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
        },

        async stop() {
            this.active = false;

            if (this.scanner) {
                await this.scanner.stop();
                this.scanner.clear();
                this.scanner = null;
            }
        },
    }));
});

let lastLocatedMoveKey = null;

window.addEventListener('barcode-move-located', (event) => {
    const moveKey = `${event.detail.moveId}:${event.detail.scannedAt}`;

    if (moveKey === lastLocatedMoveKey) {
        return;
    }

    lastLocatedMoveKey = moveKey;

    const move = document.getElementById(`move-${event.detail.moveId}`);

    if (! move) {
        return;
    }

    move.scrollIntoView({ behavior: 'smooth', block: 'center' });

    const input = move.querySelector('input[type="number"]');

    if (input) {
        setTimeout(() => {
            input.focus();
            input.select();
        }, 250);
    }
});
