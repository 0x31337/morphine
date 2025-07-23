<?php


namespace Morphine\Base\Events;


use Morphine\Base\Engine\Common;
use Morphine\Base\Engine\AppGlobals;


if( !class_exists(Events::class))
{
    class Events
    {
        /**
         * This is used by Dispatcher to validate some operations
         * as an extra layer of security (not principal security, but an additional layer).
         */
        static $referer;

        /**
         * This is used to store sessions for later use / manipulation
         * used by Dispatcher to make access control decisions
         */
        static $session;

        /**
         * This stores the current REQUEST_URI.
         * Used by Dispatcher to validate & route requests, and decide the right event.
         */
        static $URI;

        /**
         *
         */
        static $event;

        /**
         * All the remaining request data to be checked by Dispatcher channels
         */
        static $data;

        /**
         * Sanitized and Validated data will be stored here for transportation.
         */
        static $req_data;

        private \Morphine\Base\Events\Dispatcher\Dispatch $dispatcher;

        /**
         * Events constructor.
         * This object Dependency-Inject itself to avoid inheritance which causes memory leak.
         */

        function __invoke()
        {
            self::$data = $this->get_request_entity();
            $this->dispatcher = new \Morphine\Base\Events\Dispatcher\Dispatch();
            $this->extract_request(self::$data);
            ($this->dispatcher)();
        }

        private function extract_request(&$data)
        {
            self::$referer = $data['server']['HTTP_REFERER']??'';
            if(isset($referer))
            {
                $this->sanitize_referer(self::$referer);
                # Devs are not allowed to use this any further.
                unset($data['server']['HTTP_REFERER']);
            }
            else
            {
                $this->dispatcher->Flag('DIRECT_ACCESS');
            }

            $session = @$data['session'];
            if (isset($session))
            {
                $this->sanitize_session($session);
                # Devs are not allowed to use this any further.
                unset($data['session']);
            }
            if(!isset(self::$session))
            {
                $this->dispatcher->Flag('LOGGED_OUT');
            }

            self::$URI = parse_url(strtr($data['server']['REQUEST_URI'], [AppGlobals::$AppEntryPoint => '']), PHP_URL_PATH);

            # Devs are not allowed to use this any further.
            unset($data['server']['REQUEST_URI']);

            $this->stanitize_event(@$data['post']['event']);
            unset($data['post']['event']);

        }

        private function sanitize_referer(&$referer):void
        {
            $referer_host = parse_url($referer, PHP_URL_HOST);
            if ($referer_host !== self::$data['server']['HTTP_HOST'])
            {
                $this->dispatcher->Flag('STRICT_BOUNDARIES');
            }
            else
            {
                if(filter_var($referer, FILTER_VALIDATE_URL ))
                {
                    trim($referer, $referer_host);
                }
                else
                {
                    $referer = '';
                    $this->dispatcher->Flag('BAD_REFERER');
                }
            }
        }

        private function sanitize_session($session):void
        {
            if(isset($session['id']) && filter_var($session['id'], FILTER_VALIDATE_INT))
            {
                self::$session['id'] = $session['id'];
            }
            if(isset($session['secret']))
            {
                self::$session['secret'] = $session['secret'];
            }
        }

        private function stanitize_event($event = false)
        {
            self::$event = $event??'';
        }

        private function get_request_entity():array
        {
            return
                [
                    'post' => Common::reqType('POST'),
                    'get' => Common::reqType('GET'),
                    'server' => Common::reqType('SERVER'),
                    'cookie' => Common::reqType('COOKIE'),
                    'files' => Common::reqType('FILES'),
                    'request_uri' => Common::reqType('REQUEST_URI')
                ];
        }

    }
}