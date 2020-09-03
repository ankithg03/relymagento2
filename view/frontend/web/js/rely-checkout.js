require(
    [
        'jquery',
        'mage/url',
    ],
    function ($, url, redirectOnSuccessAction) {
        (function () {
            var __relyEvent = function (eventType, data) {
                this.eventType = eventType;
                this.data = data || {};
            };

            __relyEvent.eventTypes = {
                transition: 'transition',
                close: 'close',
                complete: 'complete'
            };

            // Add rely to the global scope if it's not already there.
            'use strict';

            var _global = (0, eval)('this');

            if (_global.relyEvent) {
                return;
            }

            _global.relyEvent = __relyEvent;

        })();
        (function () {

            var iframeWidth = 400,
                iframeMinWidth = 320,
                iframeInitialHeight = 704,
                iframeMinHeight = 600,
                verticalMargin = 35;

            // stackoverflow.com/questions/4169160
            var ie = (function () {
                var undef, rv = -1; // Return value assumes failure.
                var ua = window.navigator.userAgent;
                var msie = ua.indexOf('MSIE ');
                var trident = ua.indexOf('Trident/');

                if (msie > 0) {
                    // IE 10 or older => return version number
                    rv = parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10);
                } else if (trident > 0) {
                    // IE 11 (or newer) => return version number
                    var rvNum = ua.indexOf('rv:');
                    rv = parseInt(ua.substring(rvNum + 3, ua.indexOf('.', rvNum)), 10);
                }

                return ((rv > -1) ? rv : undef);
            }());

            if (ie) {
                // Polyfill for IE 9 from: https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/CustomEvent
                function CustomEvent(event, params)
                {
                    params = params || { bubbles: false, cancelable: false, detail: undefined };
                    var evt = document.createEvent('CustomEvent');
                    evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
                    return evt;
                }

                CustomEvent.prototype = window.Event.prototype;

                window.CustomEvent = CustomEvent;
            }

            // Anonymous closure-only functions.
            function sendMessageAsEvent(msg)
            {
                // dispatch custom rely event from the post message we receive.
                var myEvent = new window.CustomEvent('rely', { detail: msg });
                window.dispatchEvent(myEvent);
            }

            function isLegacyApp(url)
            {
                // very cheap way...
                return url.indexOf('app.') !== -1;
            }

            function isMobileDevice()
            {
                // Credit: http://detectmobilebrowsers.com/
                var userAgent = navigator.userAgent || navigator.vendor || window.opera;

                return /(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(userAgent) ||
                    /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(userAgent.substr(0, 4));
            }

            function getViewportHeight()
            {
                return Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
            }

            function getHtmlHeight()
            {
                var body = document.body,
                    html = document.documentElement;

                return Math.max(body.scrollHeight, body.offsetHeight, html.clientHeight, html.scrollHeight, html.offsetHeight);
            }

            function createOverlay(htmlHeight)
            {
                var overlay = document.createElement('div');
                overlay.className = 'rely-overlay';
                overlay.style.width = '100%';
                overlay.style.height = htmlHeight + 'px';
                overlay.style.position = 'absolute';
                overlay.style.left = '0px';
                overlay.style.top = '0px';
                overlay.style.display = 'table-cell';
                overlay.style.textAlign = 'center';
                overlay.style.verticalAlign = 'middle';
                overlay.style.background = 'rgba(0, 0, 0, 0.75)';
                overlay.style.zIndex = '100000';

                return overlay;
            }

            function getOverlay()
            {
                return document.querySelector('.rely-overlay');
            }

            function createCloseButton()
            {
                var closeButton = document.createElement('img');
                closeButton.src = 'http://s3-ap-southeast-1.amazonaws.com/rely-js-sdk/st/icon-close.png';
                closeButton.style.width = '50px';
                closeButton.style.height = '50px';
                closeButton.style.position = 'absolute';
                closeButton.style.top = '20px';
                closeButton.style.right = '20px';
                closeButton.style.cursor = 'pointer';
                closeButton.onclick = destroy;

                return closeButton;
            }

            function createBottomImages()
            {
                var imageWrapper = document.createElement('div');
                imageWrapper.style.width = iframeWidth + 'px';
                imageWrapper.style.minWidth = iframeMinWidth + 'px';
                imageWrapper.style.margin = '10px auto 0 auto';
                imageWrapper.style.overflow = 'hidden';

                var secureStamp = document.createElement('img');
                secureStamp.src = 'http://s3-ap-southeast-1.amazonaws.com/rely-js-sdk/st/iframe-secure.png';
                secureStamp.style.cssFloat = 'left';

                imageWrapper.appendChild(secureStamp);

                return imageWrapper;
            }

            function createIframe(src)
            {
                var iframe = document.createElement('iframe');
                iframe.id = 'rely-iframe';
                iframe.src = src;
                iframe.frameborder = 0;
                iframe.style.width = iframeWidth + 'px';
                iframe.style.minWidth = iframeMinWidth + 'px';
                iframe.style.height = iframeInitialHeight + 'px';
                iframe.style.margin = verticalMargin + 'px auto 0 auto';
                iframe.style.padding = '0px';
                iframe.style.display = 'table-row';
                iframe.style.backgroundColor = '#FFF';
                iframe.style.backgroundImage = 'url(http://s3-ap-southeast-1.amazonaws.com/rely-js-sdk/st/spinner.gif)';
                iframe.style.backgroundRepeat = 'no-repeat';
                iframe.style.backgroundPosition = '50% 50%';
                iframe.style.backgroundSize = '25%';
                iframe.style.textAlign = 'center';
                iframe.style.border = 'none';
                iframe.style.boxShadow = '0px 0px 70px 0px rgb(0, 0, 0)';

                return iframe;
            }

            function setDocumentOverflow(value)
            {
                var nodes = document.querySelectorAll('html, body');
                for (var i = 0; i < nodes.length; i++) {
                    nodes[i].style.overflowY = value;
                }
            }

            function startMonitoringWindowResize(overlay, frame)
            {
                addEventHandler(window, 'resize', debounce(function () {
                    onWindowResize(overlay, frame);
                }, 250));
            }

            function onWindowResize(overlay, frame)
            {
                var viewportHeight = getViewportHeight(),
                    htmlHeight = getHtmlHeight(),
                    windowWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;

                overlay.style.height = Math.max(viewportHeight, htmlHeight) + 'px';

                if (windowWidth < iframeWidth) {
                    frame.style.width = '100%';
                } else {
                    frame.style.width = iframeWidth + 'px';
                }
            }

            function addEventHandler(elem, eventType, handler)
            {
                if (elem.addEventListener) {
                    elem.addEventListener(eventType, handler, false);
                } else if (elem.attachEvent) {
                    elem.attachEvent('on' + eventType, handler);
                }
            }

            function removeEventHandler(elem, eventType, handler)
            {
                if (elem.removeEventListener) {
                    elem.removeEventListener(eventType, handler, false);
                } else if (elem.detachEvent) {
                    elem.detachEvent('on' + eventType, handler);
                }
            }

            function debounce(func, wait)
            {
                var timeout;

                return function () {
                    var context = this,
                        args = arguments;

                    var later = function () {
                        timeout = null;
                        func.apply(context, args);
                    };

                    var callNow = !timeout;
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);

                    if (callNow) {
                        func.apply(context, args);
                    }
                };
            }

            function destroy()
            {
                removeEventHandler(window, 'resize', onWindowResize);
                let elem = document.querySelectorAll('.rely-overlay');
                for (let i=0; i<elem.length; i++) {
                    elem[i].parentNode.removeChild(elem[i]);
                }
                setDocumentOverflow('initial');

                // setTimeout(()=>{
                //     console.log(document.referrer);
                //     window.location.href = url.build('checkout/cart');
                //     $('body').trigger('processStop');
                // },5000);

                if(document.referrer === url.build('checkout')) {
                    console.log('redirecting')
                }
                setDocumentOverflow('initial');

            }

            // rely class closure.  Exported globally as rely below.
            var __rely = {

                _overlay: undefined,

                _frame: undefined,

                _initialHtmlHeight: undefined,

                checkout: function (url) {
                    // if (isLegacyApp(url) || isMobileDevice()) {
                    if ( isMobileDevice()) {
                        window.location.href = url;
                    } else {
                        _initialHtmlHeight = getHtmlHeight();

                        // fix to scroll to top immediately
                        window.scrollTo(0, 0);

                        _overlay = createOverlay(_initialHtmlHeight);
                        _overlay.appendChild(createCloseButton());

                        _frame = createIframe(url);

                        _overlay.appendChild(_frame);
                        _overlay.appendChild(createBottomImages());

                        document.body.appendChild(_overlay);
                        setDocumentOverflow('auto');

                        startMonitoringWindowResize(_overlay, _frame);
                    }
                },

                onTransition: function () {
                    window.scroll(0, 0);
                },

                onClose: destroy,

                onComplete: function (data) {
                    console.log('complete: ', data);
                }
            };

            var __dispatcherForEvent = {};
            __dispatcherForEvent[relyEvent.eventTypes.transition] = 'onTransition';
            __dispatcherForEvent[relyEvent.eventTypes.close] = 'onClose';
            __dispatcherForEvent[relyEvent.eventTypes.complete] = 'onComplete';

            // Create IE + others compatible event handler
            var eventMethod = window.addEventListener ? 'addEventListener' : 'attachEvent';
            var eventer = window[eventMethod];
            var messageEvent = eventMethod == 'attachEvent' ? 'onmessage' : 'message';

            // Listen to message from child window
            eventer(messageEvent, function (e) {
                // Look for the rely messages and convert them to events.
                // ASM: Note, the new architecture practically negates the need for converting
                // the message to an event.  That said, if they want to sniff it, then...
                if (e.data.rely) {
                    // It's a rely event.
                    sendMessageAsEvent(e.data.msg)
                }
            }, false);

            __emptyHandler = function (evt) {
                console.log('Unexpected Event: ');
                console.log(evt.detail);
            };

            eventer('rely', function (evt) {
                var eventType = evt.detail.eventType;
                var dispatchHandler = __rely[__dispatcherForEvent[eventType]] || __emptyHandler;
                dispatchHandler(evt.detail.data || {});
                // TODO: Hook up other events.
            }, false);

            // Add rely to the global scope if it's not already there.
            'use strict';

            var _global = (0, eval)('this');

            if (_global.rely) {
                return;
            }
            _global.rely = __rely;

        })();
    }
);

