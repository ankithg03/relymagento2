define(
    [
        'jquery',
        'mage/url'
    ],
    function (
        $,
        url
    ) {
        var relyJSComponent = function (config) {

            if (config.config.popup_enabled) {
                const openModalButtons = document.querySelectorAll('[data-modal-target]');
                const closeModalButtons = document.querySelectorAll('[data-close-button]');
                const overlay = document.getElementById('rely-popup-overlay');
                const clickOverlay = document.querySelector('.overlay-click');
                openModalButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        const modal = document.querySelector(button.dataset.modalTarget);
                        openModal(modal)
                    })
                });
                /*---Close model when you click on active background---*/
                overlay.addEventListener('click', () => {
                    const modals = document.querySelectorAll('.rely-popup-modal.active');
                    modals.forEach(modal => {
                        closeModal(modal)
                    })
                });

                closeModalButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        const modal = button.closest('.rely-popup-modal');
                        closeModal(modal)
                    })
                });

                function openModal(modal)
                {
                    if (modal == null) {
                        return;
                    }
                    modal.classList.add('active');
                    overlay.classList.add('active');
                }

                function closeModal(modal)
                {
                    if (modal == null) {
                        return;
                    }
                    modal.classList.remove('active');
                    overlay.classList.remove('active');

                }

                $(document).on('click', function (event) {
                    if (event.target.className === 'rely-popup-modal active') {
                        const modals = document.querySelectorAll('.rely-popup-modal.active');
                        modals.forEach(modal => {
                            closeModal(modal)
                        });
                    }
                });
            } else {
                const openModalButtons = document.querySelectorAll('[data-modal-target]');
                openModalButtons.forEach(item => {
                    item.addEventListener('click', () => {
                        window.open('https://www.rely.sg/');
                    });
                });
            }
        };

        return relyJSComponent;
    }
);
