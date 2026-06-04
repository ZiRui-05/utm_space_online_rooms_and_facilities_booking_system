(function () {
    const queue = [];
    const batchSize = 2;
    const pauseMs = 80;
    let running = false;

    function sleep(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }

    async function waitForIdle() {
        if ('requestIdleCallback' in window) {
            await new Promise((resolve) => window.requestIdleCallback(resolve, { timeout: 500 }));
            return;
        }
        await sleep(0);
    }

    async function loadImage(img) {
        const src = img.dataset.asyncSrc;
        if (!src || img.dataset.asyncLoaded === '1') return;
        await waitForIdle();
        img.decoding = 'async';
        img.loading = img.loading || 'lazy';
        img.src = src;
        img.dataset.asyncLoaded = '1';
    }

    async function drainQueue() {
        if (running) return;
        running = true;
        while (queue.length > 0) {
            const batch = queue.splice(0, batchSize);
            await Promise.all(batch.map(loadImage));
            if (queue.length > 0) await sleep(pauseMs);
        }
        running = false;
    }

    function enqueue(root) {
        const images = Array.from((root || document).querySelectorAll('img[data-async-src]:not([data-async-loaded="1"])'));
        queue.push(...images);
        void drainQueue();
    }

    document.addEventListener('DOMContentLoaded', () => enqueue(document));
    window.chunkedImageLoader = { enqueue };
})();
