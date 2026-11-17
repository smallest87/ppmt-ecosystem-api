const EYE_OPEN_SVG = `
<svg class="w-4 h-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
</svg>`;

const EYE_CLOSED_SVG = `
<svg class="w-4 h-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
</svg>`;

function enhancePasswordInputs(root = document) {
    const inputs = root.querySelectorAll('input[type="password"]');

    inputs.forEach((input) => {
        if (input.dataset.pwToggleInit === '1') return;
        input.dataset.pwToggleInit = '1';

        const wrapper = document.createElement('div');
        wrapper.className = 'relative w-full';

        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        input.classList.add('pr-10');

        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.setAttribute('aria-label', 'Lihat kata sandi');
        toggleBtn.className = 'absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-700 cursor-pointer focus:outline-none';
        toggleBtn.innerHTML = EYE_OPEN_SVG;

        toggleBtn.addEventListener('click', () => {
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            toggleBtn.innerHTML = isHidden ? EYE_CLOSED_SVG : EYE_OPEN_SVG;
            toggleBtn.setAttribute('aria-label', isHidden ? 'Sembunyikan kata sandi' : 'Lihat kata sandi');
        });

        wrapper.appendChild(toggleBtn);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    enhancePasswordInputs();

    // Pantau jika ada modal/template Alpine.js yang baru dirender ke DOM
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) {
                    enhancePasswordInputs(node);
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
});