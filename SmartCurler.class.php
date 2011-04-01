<?php
/*
    Sample usage:
        $curler = new SmartCurler();
        $curler->get('http://www.google.com/'); // passes

        $curler = new SmartCurler();
        $curler->setMime('webpages');
        $curler->get('http://www.google.com/'); // passes

        $curler = new SmartCurler();
        $curler->setMime('text/html');
        $curler->get('http://www.google.com/'); // passes

        $curler = new SmartCurler();
        $curler->get('http://www.google.ca/intl/en/images/about_logo.gif'); // fails (default is to accept only webpage mime types)

        $curler = new SmartCurler();
        $curler->setMime('images');
        $curler->get('http://www.google.ca/intl/en/images/about_logo.gif'); // passes

        $curler = new SmartCurler();
        $curler->setMime('gif');
        $curler->get('http://www.google.ca/intl/en/images/about_logo.gif'); // passes

        $curler = new SmartCurler();
        $curler->setMime('image/gif');
        $curler->get('http://www.google.ca/intl/en/images/about_logo.gif'); // passes

        $curler = new SmartCurler();
        $curler->setMime('image/jpeg');
        $curler->get('http://www.google.ca/intl/en/images/about_logo.gif'); // fails

        $curler = new SmartCurler();
        $curler->get('graph.facebook.com/oliver.nassar/picture'); // fails, since response is image/javascript

        $curler = new SmartCurler();
        $curler->setMime('image');
        $curler->get('graph.facebook.com/oliver.nassar/picture'); // fails if javascript, passes otherwise

        $curler = new SmartCurler();
        $curler->setMime('image/jpeg');
        $curler->get('graph.facebook.com/oliver.nassar/picture'); // fails if javascript, passes otherwise

        $curler = new SmartCurler();
        $curler->setMime('javascript');
        $curler->get('graph.facebook.com/oliver.nassar/picture'); // fails if image, passes otherwise

        $curler = new SmartCurler();
        $curler->setMime('text/javascript');
        $curler->get('graph.facebook.com/oliver.nassar/picture'); // fails if image, passes otherwise

        $curler = new SmartCurler();
        $curler->setMimes('image', 'javascript');
        $curler->get('graph.facebook.com/oliver.nassar/picture'); // passes

        $curler = new SmartCurler();
        $curler->setMime('all');
        $curler->get('graph.facebook.com/oliver.nassar/picture'); // passes

        ...

        $curler = new SmartCurler();
        $curler->head('http://www.google.com/'); // passes

        $curler = new SmartCurler();
        $curler->setMimes('image', 'javascript');
        $curler->head('http://www.google.com/'); // fails

        $curler = new SmartCurler();
        $curler->head('http://www.google.ca/intl/en/images/about_logo.gif'); // passes

        $curler = new SmartCurler();
        $curler->head('graph.facebook.com/oliver.nassar/picture'); // passes
*/

    /**
     * SmartCurler class. Makes curl requests (either HEAD or GET) to a url/path
     * 
     * @version 1.0
     * @note currently has features that limit requests if file size is too
     *     large, or mime type isn't acceptable for the request
     * @note GET's are setup to, by default, accept only webpage mime types;
     *     HEAD's are setup to accept all, so you need to be specific if you
     *     want a HEAD to fail (return `false`) for certain mime type checks
     * @note all requests will fail/return `false` if a 404 is encoutered; the
     *     response can still be accessed with the `getInfo` method, however
     * @note if a response has no mime type, it will fail
     * @todo support POST requests
     */
    class SmartCurler
    {
        /**
         * __acceptable. The mime types that are acceptable for this request to
         *     return its response
         * 
         * @var array
         * @access private
         */
        private $__acceptable;

        /**
         * __auth. HTTP auth credentials
         * 
         * @var array
         * @access private
         */
        private $__auth;

        /**
         * __cookie. Path to the cookie file that should be used for temporary
         *     storage of cookies that sent back by a curl
         * 
         * @var string
         * @access private
         */
        private $__cookie;

        /**
         * __death. False signifying a response should always be returned, an int
         *     (eg. 404) marking what http code should kill the request, or an
         *     array of http code's marking when it should die
         * 
         * @var false|int|array
         * @access private
         */
        private $__death;

        /**
         * __error. Array containing details of a possible error
         * 
         * @var array
         * @access private
         */
        private $__error;

        /**
         * __headers. Array containing the request headers that will be sent with
         *     the curl
         * 
         * @var array
         * @access private
         */
        private $__headers;

        /**
         * __info. Storage of the info that was returned by the GET and HEAD
         *     calls (since a GET is always preceeded by a HEAD)
         * 
         * @var array
         * @access private
         */
        private $__info;

        /**
         * __limit. The limit, in kilobytes, that the curler will grab. This is
         *     determined by sending a HEAD request first
         * 
         * (default value: 1024)
         * 
         * @var int
         * @access private
         */
        private $__limit = 1024;

        /**
         * __mimes. Mime type mappings, used to determine if requests should be
         *     processed and/or returned
         * 
         * @note can modified if you want certain mime-types
         *     (eg. application/whatever) to be 'categorized' in a certain way
         * @var array
         * @access private
         */
        private $__mimes = array(
            'application/json' => array(
                'all',
                'javascript',
                'js',
                'json',
                'text'
            ),
            'application/x-javascript' => array(
                'all',
                'javascript',
                'js',
                'text'
            ),
            'application/xhtml+xml' => array(
                'all',
                'text',
                'webpage',
                'webpages',
                'xhtml',
                'xml'
            ),
            'application/xml' => array(
                'all',
                'text',
                'xml'
            ),

            'image/bmp' => array(
                'all',
                'bmp',
                'image',
                'images'
            ),
            'image/gif' => array(
                'all',
                'gif',
                'image',
                'images'
            ),
            'image/jpeg' => array(
                'all',
                'image',
                'images',
                'jpeg',
                'jpg'
            ),
            'image/jpg' => array(
                'all',
                'image',
                'images',
                'jpeg',
                'jpg'
            ),
            'image/pjpeg' => array(
                'all',
                'image',
                'images',
                'jpeg',
                'jpg'
            ),
            'image/png' => array(
                'all',
                'image',
                'images',
                'png'
            ),
            'image/vnd.microsoft.icon' => array(
                'all',
                'image',
                'images'
            ),
            'image/x-icon' => array(
                'all',
                'image',
                'images'
            ),
            'image/x-bitmap' => array(
                'all',
                'image',
                'images'
            ),

            'text/css' => array(
                'all',
                'css',
                'text'
            ),
            'text/html' => array(
                'all',
                'html',
                'text',
                'webpage',
                'webpages'
            ),
            'text/plain' => array(
                'all',
                'text'
            ),
            'text/javascript' => array(
                'all',
                'javascript',
                'js',
                'text'
            ),
            'text/x-javascript' => array(
                'all',
                'javascript',
                'js',
                'text'
            ),
            'text/x-json' => array(
                'all',
                'javascript',
                'js',
                'json',
                'text'
            )
        );

        /**
         * __timeout. Number of seconds to wait before timing out and failing
         * 
         * @var int
         * @access private
         */
        private $__timeout ;

        /**
         * __userAgent. The user agent that should be simulating the request
         * 
         * @var string
         * @access private
         */
        private $__userAgent;

        /**
         * __close function.
         * 
         * @access private
         * @final
         * @param resource $resource
         * @return void
         */
        final private function __close($resource)
        {
            curl_close($resource);
        }

        /**
         * __construct function.
         * 
         * @access public
         * @param int $death. (default: 404) HTTP code that should kill the
         *     request (eg. don't return the response); if false, will continue
         *     always
         * @return void
         */
        public function __construct($death = 404)
        {
            // ensure curl is instaleld
            if (!in_array('curl', get_loaded_extensions())) {
                throw new Exception('Curl extension needs to be installed.');
            }

            // ensure cookie path is writable
            if (false) {
                throw new Exception('Path *' . TMP . '/cookies.txt* must be writable');
            }

            // ensure no HTTP auth credentials are setup
            $this->__auth = array();

            // set the death code (used for marking a 'failed' curl)
            $this->__death = $death;

            // set the mime types that are acceptable, by default
            $this->setMime('webpages');

            // set path to store temp cookies in (some sites will only respond
            // if a cookie can be sent, to make sure it's not a bot)
            $this->__setCookiePath(TMP . '/cookies.txt');

            // set the request headers
            $this->setHeaders(array(
                'Connection' => 'keep-alive',
                'Accept-Language' => 'en-us,en;q=0.5'
            ));

            // set timeout in seconds
            $this->setTimeout(5);

            // set user agent
            $this->setUserAgent(
                'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; en-US; ' .
                'rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12'
            );
        }

        /**
         * __getHeaders function. Parses and returns the headers for the curl request
         * 
         * @access private
         * @final
         * @return array headers formatted to be correctly formed for an HTTP request
         */
        final private function __getHeaders()
        {
            $formatted = array();
            foreach ($this->__headers as $name => $value) {
                array_push($formatted, ($name) . ': ' . ($value));
            }
            return $formatted;
        }

        /**
         * __getResource function. Creates a curl resource, set's it up, and
         *     returns it's reference
         * 
         * @access private
         * @final
         * @param string $url
         * @param bool $head. (default: false) whether or not this is a HEAD
         *     request, in which case no response-body is returned
         * @return resource curl resource reference
         */
        final private function __getResource($url, $head = false)
        {
            // init call, headers, user agent
            $resource = curl_init($url);
            curl_setopt($resource, CURLOPT_HTTPHEADER, $this->__getHeaders());
            curl_setopt($resource, CURLOPT_HEADER, false);
            curl_setopt($resource, CURLOPT_USERAGENT, $this->__userAgent);
            curl_setopt($resource, CURLOPT_ENCODING, 'gzip,deflate');

            // authentication
            if (!empty($this->__auth)) {
                curl_setopt(
                    $resource,
                    CURLOPT_USERPWD,
                    ($this->__auth['username']) . ':' . ($this->__auth['password'])
                );
            }

            // cookies
            curl_setopt($resource, CURLOPT_COOKIEFILE, $this->__cookie);
            curl_setopt($resource, CURLOPT_COOKIEJAR, $this->__cookie);

            // time allowances
            curl_setopt($resource, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($resource, CURLOPT_TIMEOUT, $this->__timeout);

            // https settings
            curl_setopt($resource, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($resource, CURLOPT_SSL_VERIFYHOST, 1);
            curl_setopt($resource, CURLOPT_FRESH_CONNECT, true);

            // response, redirection, and HEAD request settings
            curl_setopt($resource, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($resource, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($resource, CURLOPT_MAXREDIRS, 10);
            curl_setopt($resource, CURLOPT_NOBODY, $head);

            // return resource reference (all set up and ready to go)
            return $resource;
        }

        /**
         * __setCookiePath function.
         * 
         * @access private
         * @final
         * @param string $path
         * @return void
         */
        final private function __setCookiePath($path)
        {
            if(file_exists($path)) {
                $this->__cookie = $path;
            } else {
                $resource = fopen($path, 'w');
                $this->__cookie = $path;
                fclose($resource);
            }
        }

        /**
         * __valid function. Ensures that a request is valid, based on the
         *     http code, mime type and content length returned
         * 
         * @access private
         * @final
         * @return bool whether or not the request is valid to be processed
         */
        final private function __valid()
        {
            // should be killed; die
            if (in_array($this->__info['http_code'], (array) $this->__death)) {
                $this->__error = array(
                    'message' => ($this->__info['http_code']) .
                        ' error while trying to retrieve ' .
                        ($this->__info['url'])
                );
                return false;
            }

            // check if mime type requirement met
            $mimes = $this->getMimes();
            $pieces = explode(';', $this->__info['content_type']);
            $mime = current($pieces);
            if (!in_array($mime, $mimes)) {

                // make error, and return false (eg. `content_type` didn't
                // match; info still available for usage via
                // `$this->getInfo`)
                $this->__error = array(
                    'message' => 'Mime-type requirement not met. Resource is ' .
                        current(explode(';', $this->__info['content_type'])) .
                        '. You were hoping for one of: ' .
                        implode(', ', $this->getMimes()) . '.'
                );
                return false;
            }

            // greater than maximum allowed
            if($this->__info['download_content_length'] > ($this->__limit * 1024)) {

                // make error, return false
                $this->__error = array(
                    'message' => ('File size limit reached. Limit was set to ') .
                        ($this->__limit) . ('kb. ') . ('Resource is ') .
                        round(($this->__info['download_content_length'] / 1024), 2) .
                        ('kb.')
                );
                return false;
            }

            // return as valid
            return true;
        }

        /**
         * addMime function. Adds a specific mime type to the acceptable range
         *     for a return/response
         * 
         * @access public
         * @final
         * @param string $mime
         * @return void
         */
        final public function addMime($mime)
        {
            $this->__acceptable[] = $mime;
        }

        /**
         * addMimes function. Adds passed in mime types to the array tracking
         *     which are are acceptable to be returned
         * 
         * @access public
         * @final
         * @return void
         */
        final public function addMimes()
        {
            $args = func_get_args();
            foreach ($args as $mime) {
                $this->addMime($mime);
            }
        }

        /**
         * get function.
         * 
         * @access public
         * @final
         * @param string $url
         * @return array|false
         */
        final public function get($url)
        {
            // execute HEAD call, and check if invalid
            $this->head($url);
            if (!$this->__valid()) {

                /**
                 * failed HEAD, so return `false` (info of the call and error
                 *     details still available through `$this->getInfo` and
                 *     `$this->getError`, respectively
                 */
                return false;
            }

            // mime type setting
            $this->setHeader('Accept', implode(',', $this->getMimes()));
            $resource = $this->__getResource($url);

            // make the GET call, storing the response; store the info
            $response = curl_exec($resource);
            $this->__info = curl_getinfo($resource);

            // error founded
            if (curl_errno($resource) !== '0') {
                $this->__error = array(
                    'code' => curl_errno($resource),
                    'message' => curl_error($resource)
                );
            }

            // close the resource
            $this->__close($resource);

            // give the response back :)
            return $response;
        }

        /**
         * getError function. Get details on the error that occured
         * 
         * @access public
         * @final
         * @return array
         */
        final public function getError()
        {
            if (is_null($this->__error)) {
                return array();
            }
            return $this->__error;
        }

        /**
         * getInfo function. Grabs the previously store info for the curl call
         * 
         * @access public
         * @final
         * @return array
         */
        final public function getInfo()
        {
            return $this->__info;
        }

        /**
         * getMimes function. Maps the mime types specified and returns them for
         *     the curl requests
         * 
         * @access public
         * @final
         * @return array mime types formatted to the be correctly formed for an
         *     HTTP request
         */
        final public function getMimes()
        {
            $mimes = array();
            foreach ($this->__mimes as $mime => $buckets) {
                $intersection = array_intersect($this->__acceptable, $buckets);
                if (in_array($mime, $this->__acceptable)) {
                    array_push($mimes, $mime);
                } elseif (!empty($intersection)) {
                    $mimes = array_merge($mimes, (array) $mime);
                }
            }
            return array_unique($mimes);
        }

        /**
         * head function. Make a HEAD call to the passed in url
         * 
         * @note intrinsically, HEAD requests don't have a response, just the
         *     info from the server
         * @note a HEAD request will still fail/return `false` if the mime type
         *     requirement isn't met
         * @access public
         * @final
         * @param string $url the url to run the HEAD call again
         * @return array
         */
        final public function head($url)
        {
            /**
             * accept all content (ignored by HEAD requests, just put in for
             *     clarity); grab the resource
             */
            $this->setHeader('Accept', '*/*');
            $resource = $this->__getResource($url, true);

            // make the HEAD call; store the info
            curl_exec($resource);
            $this->__info = curl_getinfo($resource);

            // error founded
            if (curl_errno($resource) !== '0') {
                $this->__error = array(
                    'code' => curl_errno($resource),
                    'message' => curl_error($resource)
                );
            }

            // close the resource
            $this->__close($resource);

            // return info (head-headers)
            return $this->__info;
        }

        /**
         * reset function. Resets the curler to __construct phase for further use
         * 
         * @access public
         * @final
         * @return void
         */
        final public function reset()
        {
            $this->__construct($this->__death);
        }

        /**
         * setAuth function.
         * 
         * @access public
         * @final
         * @param string $username
         * @param string $password
         * @return void
         */
        final public function setAuth($username, $password)
        {
            $this->__auth = array(
                'username' => $username,
                'password' => $password
            );
        }

        /**
         * setHeader function. Sets a header for the request being made
         * 
         * @note note using `array_push` here since I want to be able to
         *     overwrite specific headers (eg. mime type options)
         * @access public
         * @final
         * @param string $name
         * @param string $value
         * @return void
         */
        final public function setHeader($name, $value)
        {
            $this->__headers[$name] = $value;
        }

        /**
         * setHeaders function. Sets a group of headers at once, for the request
         * 
         * @access public
         * @final
         * @param array $headers
         * @return void
         */
        final public function setHeaders($headers)
        {
            foreach ($headers as $name => $value) {
                $this->setHeader($name, $value);
            }
        }

        /**
         * setLimit function. Set's the maximum number of kilobytes that can be
         *     downloaded/requested in a GET request
         * 
         * @access public
         * @final
         * @param int|float $kilobytes
         * @return void
         */
        final public function setLimit($kilobytes)
        {
            $this->__limit = $kilobytes;
        }

        /**
         * setMime function. Set's the acceptable mime's for content type to a
         *     specific one
         * 
         * @access public
         * @final
         * @param string $mime
         * @return void
         */
        final public function setMime($mime)
        {
            $this->setMimes($mime);
        }

        /**
         * setMimes function. Stores which mime types can be accepted in the
         *     request
         * 
         * @note if false specified (such as setMime(false) or setMimes(false)),
         *     then no mimes are set as being allowed (eg. good for clearing out
         *     any previously set acceptable mime-types)
         * @access public
         * @final
         * @return void
         */
        final public function setMimes()
        {
            $args = func_get_args();
            $this->__acceptable = array();
            if (!in_array(false, $args)) {
                $this->__acceptable = $args;
            }
        }

        /**
         * setTimeout function.
         * 
         * @access public
         * @final
         * @param string $seconds
         * @return void
         */
        final public function setTimeout($seconds)
        {
            $this->__timeout = $seconds;
        }

        /**
         * setUserAgent function.
         * 
         * @access public
         * @final
         * @param string $str
         * @return void
         */
        final public function setUserAgent($str)
        {
            $this->__userAgent = $str;
            $this->setHeader('User-Agent', $str);
        }
    }

?>

