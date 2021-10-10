<?php

    // load parser
    array_map( fn( $file ) => require_once( $file ), glob( __DIR__ . '/Parser/**.php' ) );

    class pp {

        private ParserInput $parserInput;

        private Parser $parser;

        public function __construct( Parser $parser, ParserInput $input ) {
            $this->parser      = $parser;
            $this->parserInput = $input;
        }

        public function parse() {

            print_r( $this->parser->run( $this->parserInput, new ParserState ) );
        }

    }

    var_dump(( new pp(

        between(char("{"), char("}"), string() ),

        new ParserInput( '{hello}' )
    ))->parse() );
