// noinspection JSUnusedLocalSymbols,JSUnresolvedVariable

/**
 * Admin common JS routines (used on all admin screens).
 *
 * @package WP-Plugins-Core
 */
'use strict';


document.addEventListener( "DOMContentLoaded", function() {

    /**
     * Retrieves URL param.
     *
     * @param   {string} param Param to retrieve the values for.
     * @return {string} Empty string if no params, param value otherwise.
     */
    function getUrlParam( param ) {
        let urlParams = new URLSearchParams( window.location.search ),
            val       = urlParams.get( param );
        if ( val ) {
            return val;
        } else {
            return '';
        }
    }

    /**
     * Retrieves URL param.
     *
     * @param   {string} key Key to add.
     * @param   {string} value Value to add.
     *
     * @return {string} Empty string if no params, param value otherwise.
     */
    function addUrlParam( key, value ) {
        let urlParams = new URLSearchParams( window.location.search ),
            val       = urlParams.get( key );

        urlParams.set( key, value );

        window.history.pushState( {}, '', '?' + urlParams );
    }

    /*
     * Tabs.
     */

    const tabs = document.querySelectorAll( '.wp-plugins-core-tab-content' );
    if ( tabs.length ) {

        /**
         * Dispatches tab click event.
         *
         * @event document#tmm-wp-plugins-core-tab-switch Contains .wp-plugins-core-single-tab element as data.tab.
         *
         * @param {Element} tab A tab.
         */
        function dispatchTabClickEvent( tab ) {
            const event = new Event( 'tmm-wp-plugins-core-tab-switch' );
            event.data = { 'tab': tab };
            document.dispatchEvent( event );
        }

        /**
         * When a tab has been selected.
         *
         * @param {Element} tab A tab.
         */
        function tabSelect( tab ) {
            const tabSlug                      = tab.getAttribute( 'data-tab-name' );
            const previouslySelectedTabContent = document.querySelector( '.wp-plugins-core-tab-content.selected' ),
                targetTabContent               = document.querySelector( `.wp-plugins-core-tab-content[data-tab-name="${tabSlug}"]` ),
                previousSingleTab              = document.querySelector( '.wp-plugins-core-single-tab.selected' );

            if ( previouslySelectedTabContent !== targetTabContent ) {
                if ( previousSingleTab ) {
                    previousSingleTab.classList.remove( 'selected' );
                }
                if ( previouslySelectedTabContent ) {
                    previouslySelectedTabContent.classList.remove( 'selected' );
                }

                tab.classList.add( 'selected' );
                targetTabContent.classList.add( 'selected' );

                addUrlParam( 'plugin-selected-tab', tabSlug );

                dispatchTabClickEvent( tab );
            }
        }

        const tabsContainer = document.querySelector( '.wp-plugins-core-tabs-container' );

        // Collects headings from tabs.

        let tabsData = [];
        tabs.forEach( tab => {
            const tabHeadingElem = tab.querySelector( '.wp-plugins-core-tab-heading' );
            if ( tabHeadingElem ) {
                const tabName = tab.getAttribute( 'data-tab-name' );
                tabsData.push( [ tabName, tabHeadingElem.innerHTML ] );
            }
        } );

        // Adds headings to Tabs container.

        let headingsHTML = '';
        tabsData.forEach( tabData => {
            const tabSlug = tabData[0],
                tabName = tabData[1];
            headingsHTML += `<li class="wp-plugins-core-single-tab" data-tab-name="${tabSlug}">${tabName}</li>`;
        } );

        tabsContainer.insertAdjacentHTML(
            'afterbegin',
            `
                <span class="next-child-expand-button dashicons dashicons-menu"></span>
                <ul class="wp-plugins-core-tabs">${headingsHTML}</ul>
                `
        );

        // Adds click listeners.

        const clickableTabs = document.querySelectorAll( '.wp-plugins-core-single-tab' );
        clickableTabs.forEach( tab => {
            const tabSlug = tab.getAttribute( 'data-tab-name' );
            tab.addEventListener( 'click', function() {
                tabSelect( tab );
            } );
        } );

        // Autoload.

        const selectedTabSlug = getUrlParam( 'plugin-selected-tab' ),
            targetTab         = document.querySelector( `.wp-plugins-core-single-tab[data-tab-name="${selectedTabSlug}"]` );

        if ( selectedTabSlug && targetTab ) {
            tabSelect( targetTab );
        } else { // Selects the first tab.
            tabSelect( clickableTabs[0] );
        }

        // Tabs expand button.

        const tabsExpandButton = tabsContainer.querySelector( '.next-child-expand-button' );
        tabsExpandButton.addEventListener(
            'click',
            () => {
                tabsExpandButton.classList.toggle( 'expanded' );
            }
        );
    }

    const notification = document.querySelector( '.wp-plugins-core-notification' );

    /**
     * Drip notifications container.
     */
    if ( notification ) {

        const dripNotification = document.querySelectorAll( '.wp-plugins-core-notification.drip' );

        /**
         * Drip notifications container.
         */
        if ( dripNotification.length ) {
            const lastDripNotification = dripNotification[ dripNotification.length - 1 ];

            const dripsContainer = document.createElement( 'div' );
            dripsContainer.classList.add( 'wp-plugins-core-drip-notifications-container', 'notice', 'is-dismissible', 'notice-info' );

            lastDripNotification.parentNode.insertBefore( dripsContainer, lastDripNotification );

            dripsContainer.insertAdjacentHTML(
                'beforeend',
                `
                <div class="swiper-wrapper"></div>
                <div class="wp-plugins-core-drip-pagination">
                    <div class="wp-plugins-core-drip-button-prev">«</div>
                    <div class="wp-plugins-core-drip-button-next"">»</div>
                </div>
            `
            );

            const dripButtonPrev = dripsContainer.querySelector( '.wp-plugins-core-drip-button-prev' );
            const dripButtonNext = dripsContainer.querySelector( '.wp-plugins-core-drip-button-next' );

            dripsContainer.style.display = 'none';

            const swiperWrapper = dripsContainer.querySelector( '.swiper-wrapper' );

            dripNotification.forEach( dripNotification => {
                swiperWrapper.append( dripNotification );
                dripNotification.classList.add( 'swiper-slide' );
                dripNotification.classList.remove( 'notice', 'notice-info' );
            } );

            dripButtonPrev.style.visibility    = 'hidden'; // Fix the bug when it appears and shifts the layout.
            dripButtonPrev.style.pointerEvents = 'none';   // Fix the bug when it appears and shifts the layout.

            /**
             * Shows drip notifications container.
             */
            function showDripContainer() {
                dripsContainer.style.display = '';

                dripButtonNext.addEventListener( 'click', () => {
                    dripButtonPrev.style.visibility = '';    // Fix the bug when it appears and shifts the layout.
                    dripButtonPrev.style.pointerEvents = ''; // Fix the bug when it appears and shifts the layout.
                } );
            }

            if ( dripNotification.length > 1 ) {
                const swiper = new Swiper( '.wp-plugins-core-drip-notifications-container', {
                    slidesPerView: 1,
                    spaceBetween: 80,
                    navigation: {
                        prevEl: ".wp-plugins-core-drip-button-prev",
                        nextEl: ".wp-plugins-core-drip-button-next",
                    },
                    on: {
                        init: showDripContainer,
                    }
                } );
            } else {
                document.querySelector( '.wp-plugins-core-drip-pagination' ).remove();
                dripsContainer.querySelector( '.swiper-slide' ).classList.add( 'swiper-slide-active' );
                showDripContainer();
            }
        }

        /**
         * Saves viewed notifications.
         *
         * @param {Array} ids The list of viewed notifications to save.
         */
        function saveViewedNotifications( ids ) {
            let data = new URLSearchParams( {
                'action':     'wp_plugins_core_save_viewed_notifications',
                'nonceToken': wpPluginsCoreAdminCommon.nonceToken,
                'ids':       JSON.stringify( ids ),
            } );

            /**
             * Sends the data to the server.
             *
             * Uses Beacon API if available, and otherwise fallbacks to AJAX.
             *
             * @param {Object} data
             */
            ( function sendData( data ) {
                if ( navigator.sendBeacon ) {
                    navigator.sendBeacon( ajaxurl, data );
                } else {
                    const params = {
                        method:      'POST',
                        credentials: 'same-origin',
                        headers:     new Headers( { 'Content-Type': 'application/x-www-form-urlencoded' } ),
                        body:        data
                    };

                    fetch( ajaxurl, params ).then( response => {
                        return response.json();
                    } ).then( response => {
                        if ( true !== response.success ) {
                            Swal.fire(
                                'WP Plugins Core AJAX ' + wpPluginsCoreAdminCommon.txt.error,
                                wpPluginsCoreAdminCommon.txt.somethingWentWrong,
                                'error'
                            );
                        }
                    } );
                }
            } ) ( new URLSearchParams( data ) );
        }

        /**
         * Figure out when dismiss buttons were added to attach event listener.
         */
        const interval = setInterval( function() {
            const noticeDismissButtons = document.querySelectorAll(
                '.wp-plugins-core-notification button.notice-dismiss, .wp-plugins-core-drip-notifications-container button.notice-dismiss' );

            if ( noticeDismissButtons.length ) {
                clearInterval( interval );
                noticeDismissButtons.forEach( button => {
                    button.addEventListener( 'click', function() {
                        const noticeContainer = button.closest( '.notice' );
                        let ids = [];

                        if ( noticeContainer.classList.contains( 'wp-plugins-core-drip-notifications-container' ) ) { // Drip notifications container.
                            function getPreviousSlides( activeSlide ) {
                                let siblings = [];
                                let previousSibling = activeSlide.previousElementSibling
                                while ( previousSibling ) {
                                    siblings.push( previousSibling );
                                    previousSibling = previousSibling.previousElementSibling
                                }
                                return siblings;
                            }

                            const activeSlide = noticeContainer.querySelector( '.swiper-slide-active' );
                            [ activeSlide, ...getPreviousSlides( activeSlide ) ].forEach( slide => {
                                ids.push( slide.getAttribute( 'data-notification-id' ) );
                            } );
                        } else { // Usual notification container.
                            ids.push( noticeContainer.getAttribute( 'data-notification-id' ) );
                        }

                        saveViewedNotifications( ids.map( Number ) );
                    } );
                } );
            }
        }, 50 );
    }
} );
