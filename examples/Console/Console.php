<?php declare( strict_types = 1 );

    class Console {

        const STYLE_NONE      = 0;
        const STYLE_BLACK     = 30;
        const STYLE_RED       = 31;
        const STYLE_GREEN     = 32;
        const STYLE_YELLOW    = 33;
        const STYLE_BLUE      = 34;
        const STYLE_PURPLE    = 35;
        const STYLE_CYAN      = 36;
        const STYLE_WHITE     = 37;

        const STYLE_REGULAR   = "0";
        const STYLE_BOLD      = "1";
        const STYLE_UNDERLINE = "4";

        protected $currentFgColor   = SELF::STYLE_NONE;
        protected $currentBgColor   = SELF::STYLE_NONE;
        protected $currentFontStyle = SELF::STYLE_REGULAR;
        protected $selectedStream;
        protected $stdout           = STDOUT;
        protected $stderr           = STDERR;
        protected $linePrefixFunc;
        protected $hadLineOutput    = false;
        protected $dimensions       = null;
        protected $inTerminal       = false;
        protected $colorSupported   = null;
        protected $forceColor       = false;

        /**
         *
         * @var ParameterBag
         */
        protected $argv;

        public static function getInstance(): Console {
            return new static( isset( $_SERVER["argv"] ) ? $_SERVER["argv"] : [] );
        }

        public function __construct() {

            $this->selectedStream = &$this->stdout;
            $this->argv           = new ParameterBag( $_SERVER["argv"] ?? [] );

            $this->inTerminal = isset( $_SERVER['TERM'] );
        }

        public static function isCli(): bool {
            return php_sapi_name() === "cli";
        }

        public function getArgv(): ParameterBag {
            return $this->argv;
        }

        public function getArgvAsString(): string {

            // omit first element
            return implode( " ", $this->argv->except( [ 0 ] ) );
        }

        public function setPrefixCallback( callable $func ): Console {
            $this->linePrefixFunc = $func;
            return $this;
        }

        public function toSTDOUT(): Console {
            $this->selectedStream = &$this->stdout;
            return $this;
        }

        public function toSTDERR(): Console {
            $this->selectedStream = &$this->stderr;
            return $this;
        }

        public function write( $text, $color = null ): Console {

            if ( $color !== null ) {
                $this->setForegroundColor( $color );
            }

            if ( $this->currentFontStyle || $this->currentBgColor || $this->currentFgColor ) {

                // set current color
                fwrite( $this->selectedStream, "\033[{$this->currentFgColor}m" );
            }

            if ( $this->linePrefixFunc !== null && $this->hadLineOutput !== true ) {
                fwrite( $this->selectedStream, ($this->linePrefixFunc)() );
                $this->hadLineOutput = true;
            }

            fwrite( $this->selectedStream, $text );

            // clear color output again
            if ( $this->currentFontStyle || $this->currentBgColor || $this->currentFgColor ) {
                fwrite( $this->selectedStream, "\033[0m" );
            }

            if ( $color !== null ) {
                $this->setForegroundColor( static::STYLE_NONE );
            }

            return $this;
        }

        public function writeLn( $text, $color = null ): Console {
            return $this->write( $text, $color )->eol();
        }

        public function cr(): Console {
            fwrite( $this->selectedStream, "\r" );
            return $this;
        }

        public function eol(): Console {
            $this->write( PHP_EOL );
            $this->hadLineOutput = false;
            return $this;
        }

        public function writePadded( $text, $padding = 4, $paddingChar = " ", $color = null ): Console {
            $this->write( str_repeat( $paddingChar, $padding ) );
            $this->write( $text, $color );

            return $this;
        }

        // this is blocking
        public function read() {
            return fgets( STDIN );
        }

        public function setFontFeature( int $style ): Console {
            $this->currentFontStyle = $style;
            return $this;
        }

        public function setBackgroundColor( int $color ): Console {
            $this->currentBgColor = $color + 10;
            return $this;
        }

        public function setForegroundColor( int $color ): Console {
            $this->currentFgColor = $color;
            return $this;
        }

        public function cls(): Console {
            fwrite( $this->selectedStream, "\033[2J" );
            return $this;
        }

        public function up( int $amount = 1 ): Console {
            fwrite( $this->selectedStream, "\033[{$amount}A" );
            return $this;
        }

        public function down( int $amount = 1 ): Console {
            fwrite( $this->selectedStream, "\033[{$amount}B" );
            return $this;
        }

        public function right( int $amount = 1 ): Console {
            fwrite( $this->selectedStream, "\033[{$amount}C" );
            return $this;
        }

        public function left( int $amount = 1 ): Console {
            fwrite( $this->selectedStream, "\033[{$amount}D" );
            return $this;
        }

        /**
         * Hides the cursor
         */
        public function hide() {
            fwrite( $this->selectedStream, "\033[?25l" );
        }

        /**
         * Enable/Disable Auto-Wrap
         *
         * @param bool $wrap
         */
        public function wrap( $wrap = true ) {
            if ( $wrap ) {
                fwrite( $this->selectedStream, "\033[?7h" );
            } else {
                fwrite( $this->selectedStream, "\033[?7l" );
            }
        }

        /**
         * Shows the cursor
         */
        public function show() {
            fwrite( $this->selectedStream, "\033[?25h\033[?0c" );
        }

        /**
         * stores cursor position
         * @return \Wrapped\_\Cli\Console
         */
        public function push(): Console {
            fwrite( $this->selectedStream, "\033[s" );
            return $this;
        }

        /**
         * restores cursor position
         * @return \Wrapped\_\Cli\Console
         */
        public function pop(): Console {
            fwrite( $this->selectedStream, "\033[u" );
            return $this;
        }

        public function jump( int $x = 0, int $y = 0 ): Console {
            fwrite( $this->selectedStream, "\033[{$y};{$x}H" );
            return $this;
        }

        /**
         * reset all style features
         */
        public function __destruct() {
            if ( $this->currentFgColor !== self::STYLE_NONE || $this->currentBgColor !== self::STYLE_NONE ) {
                fwrite( $this->selectedStream, "\033[0m" );
            }
        }

        public function getWidth(): ?int {

            if ( $this->dimensions === null ) {
                $this->updateDimensions();
            }

            return $this->dimensions[0] ?? null;
        }

        public function getHeight(): ?int {

            if ( $this->dimensions === null ) {
                $this->updateDimensions();
            }

            return $this->dimensions[1] ?? null;
        }

        public function updateDimensions(): bool {

            $this->dimensions[0] = (int) shell_exec( 'tput cols' );
            $this->dimensions[1] = (int) shell_exec( 'tput lines' );

            return true;
        }

        public function forceColorOutput( $bool = true ) {
            $this->forceColor = $bool;
            return $this;
        }

        public function supportsColor(): bool {
            return ((int) shell_exec( 'tput colors' )) > 1;
        }

    }
