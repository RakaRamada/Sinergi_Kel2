/**
 * Modern Modal System for Sinergi Admin
 * Uses Tailwind CSS classes.
 */

const AdminModal = {
    /**
     * Show a modern alert modal
     * @param {string} title 
     * @param {string} message 
     * @param {string} type 'success' | 'error' | 'warning' | 'info'
     */
    showAlert: function(title, message, type = 'info') {
        this._removeExisting();

        let iconColor = 'text-gray-600';
        let bgColor = 'bg-gray-100';
        let iconSvg = '';

        if (type === 'success' || title.toLowerCase().includes('berhasil')) {
            iconColor = 'text-green-600';
            bgColor = 'bg-green-100';
            iconSvg = `<svg class="h-6 w-6 ${iconColor}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>`;
        } else if (type === 'error' || type === 'element' || title.toLowerCase().includes('gagal')) {
            iconColor = 'text-red-600';
            bgColor = 'bg-red-100';
            iconSvg = `<svg class="h-6 w-6 ${iconColor}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>`;
        } else {
            // Info / Warning
            iconSvg = `<svg class="h-6 w-6 ${iconColor}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
        }

        const overlay = document.createElement('div');
        overlay.id = 'modern-modal-overlay';
        overlay.className = 'fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-sm transition-opacity duration-300';
        
        overlay.innerHTML = `
            <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full text-center border border-gray-100 transform transition-all scale-100 animate-bounce-in">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full ${bgColor} mb-4">
                    ${iconSvg}
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">${title}</h3>
                <p class="text-sm text-gray-500 mb-6 leading-relaxed">${message}</p>
                <button onclick="AdminModal.close()" class="w-full bg-black text-white hover:bg-gray-800 rounded-xl px-4 py-3 transition-colors font-medium cursor-pointer shadow-lg hover:shadow-xl transform active:scale-95 duration-200">
                    OK, Mengerti
                </button>
            </div>
        `;

        document.body.appendChild(overlay);
        // Play sound effect if needed (optional)
    },

    /**
     * Show a modern confirm modal returning a Promise
     * @param {string} title 
     * @param {string} message 
     * @returns {Promise<boolean>}
     */
    showConfirm: function(title, message) {
        return new Promise((resolve) => {
            this._removeExisting();

            const overlay = document.createElement('div');
            overlay.id = 'modern-modal-overlay';
            overlay.className = 'fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-sm transition-opacity duration-300';
            
            overlay.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full text-center border border-gray-100 transform transition-all scale-100 animate-bounce-in">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 mb-4">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">${title}</h3>
                    <p class="text-sm text-gray-500 mb-6 leading-relaxed">${message}</p>
                    <div class="flex gap-3">
                        <button id="modal-btn-cancel" class="flex-1 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-xl px-4 py-2.5 transition-colors font-medium cursor-pointer">
                            Batal
                        </button>
                        <button id="modal-btn-confirm" class="flex-1 bg-black text-white hover:bg-gray-800 rounded-xl px-4 py-2.5 transition-colors font-medium cursor-pointer shadow-lg hover:shadow-xl">
                            Ya, Lanjutkan
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);

            document.getElementById('modal-btn-confirm').onclick = () => {
                this.close();
                resolve(true);
            };

            document.getElementById('modal-btn-cancel').onclick = () => {
                this.close();
                resolve(false);
            };
        });
    },

    close: function() {
        this._removeExisting();
    },

    _removeExisting: function() {
        const existing = document.getElementById('modern-modal-overlay');
        if (existing) existing.remove();
    }
};

// Global shortcuts for compatibility
window.showModernAlert = (title, message, type) => AdminModal.showAlert(title, message, type);
window.showModernConfirm = (title, message) => AdminModal.showConfirm(title, message);

// Add required styles dynamically if not present
if (!document.getElementById('admin-modal-styles')) {
    const style = document.createElement('style');
    style.id = 'admin-modal-styles';
    style.innerHTML = `
        @keyframes bounceInModal {
            0% { opacity: 0; transform: scale(0.95); }
            100% { opacity: 1; transform: scale(1); }
        }
        .animate-bounce-in { animation: bounceInModal 0.2s ease-out forwards; }
    `;
    document.head.appendChild(style);
}
