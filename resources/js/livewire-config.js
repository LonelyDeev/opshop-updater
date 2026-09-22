/**
 * Must be imported BEFORE the Livewire ESM build.
 * Defining this object prevents Livewire's built-in auto-start
 * (we start it explicitly in app.js) and provides the CSRF token
 * + the update endpoint URI (normally injected via data-update-uri).
 */
window.livewireScriptConfig = {
    csrf: document.querySelector('meta[name="csrf-token"]')?.content,
    progressBar: true,
    uri: '/livewire/update',
};
