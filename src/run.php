<?php

    // load parser
    array_map( fn( $file ) => require_once( $file ), glob( __DIR__ . '/Parser/**.php' ) );

    $file = '/usr/bin/false';

    $filesize = filesize( $file );
    $fp       = fopen( $file, 'rb' );
    $raw      = fread( $fp, $filesize );
    fclose( $fp );

    $bin = array_values( unpack( sprintf( 'C%d', $filesize ), $raw ) );

    $tag      = fn( $name ) => fn( $value ) => [ 'type' => $name, "value" => $value ];
    $flatTags = fn( $result ) => array_combine(
            array_map( fn( $i ) => $i['type'], $result ),
            array_map( fn( $i ) => $i['value'], $result )
    );

    $input  = new ParserBinaryInput( $bin );
    $parser = sequenceOf(
        sequenceOf(
            uint( 8 )->map( fn( $i ) => dechex( $i ) ),
            uint( 8 )->map( fn( $i ) => chr( $i ) ),
            uint( 8 )->map( fn( $i ) => chr( $i ) ),
            uint( 8 )->map( fn( $i ) => chr( $i ) ),
        )->map( $tag( "header" ) ),
        uint( 8 )->map( fn( $i ) => $i === 1 ? '32bit' : '64bit' )->map( $tag( 'plattform' ) ),
        uint( 8 )->map( fn( $i ) => $i === 1 ? 'little endian' : 'big endian' )->map( $tag( 'endianess' ) ),
        uint( 8 )->map( $tag( 'version' ) ),
        uint( 8 )
        ->map( fn( $i ) => [
            0x00 => "System V",
            0x01 => "HP-UX",
            0x02 => "NetBSD",
            0x03 => "Linux",
            0x04 => "GNU Hurd",
            0x06 => "Solaris",
            0x07 => "AIX",
            0x08 => "IRIX",
            0x09 => "FreeBSD",
            0x0A => "Tru64",
            0x0B => "Novell Modesto",
            0x0C => "OpenBSD",
            0x0D => "OpenVMS",
            0x0E => "NonStop Kernel",
            0x0F => "AROS",
            0x10 => "Fenix OS",
            0x11 => "CloudABI",
            0x12 => "Stratus Technologies OpenVOS",
            ][$i] ?? 'unknown' )
        ->map( $tag( 'os ( mostly wrong )' ) ),
        uint( 8 )->map( $tag( 'ABI Version' ) ),
        sequenceOf( ... array_fill( 1, 7 * 8, zero() ) )->map( fn( $i ) => '' )->map( $tag( 'unsed' ) ),
        uint( 16 )->map( fn( $i ) => [
            0x0000 => "ET_NONE",
            0x0100 => "ET_REL",
            0x0200 => "ET_EXEC",
            0x0300 => "ET_DYN",
            0x0400 => "ET_CORE",
            0xFE00 => "ET_LOOS",
            0xFEFF => "ET_HIOS",
            0xFF00 => "ET_LOPROC",
            0xFFFF => "ET_HIPROC",
            ][$i] )->map( $tag( 'object type' ) ),
        uint( 8 )->map( fn( $i ) => [
            0x00  => "No specific instruction set",
            0x01  => "AT&T WE 32100",
            0x02  => "SPARC",
            0x03  => "x86",
            0x04  => "Motorola 68000 (M68k)",
            0x05  => "Motorola 88000 (M88k)",
            0x06  => "Intel MCU",
            0x07  => "Intel 80860",
            0x08  => "MIPS",
            0x09  => "IBM_System/370",
            0x0A  => "MIPS RS3000 Little-endian",
            0x0E  => "Hewlett-Packard PA-RISC",
            0x0F  => "Reserved for future use",
            0x13  => "Intel 80960",
            0x14  => "PowerPC",
            0x15  => "PowerPC (64-bit)",
            0x16  => "S390, including S390x",
            0x17  => "IBM SPU/SPC",
            0x24  => "NEC V800",
            0x25  => "Fujitsu FR20",
            0x26  => "TRW RH-32",
            0x27  => "Motorola RCE",
            0x28  => "ARM (up to ARMv7/Aarch32)",
            0x29  => "Digital Alpha",
            0x2A  => "SuperH",
            0x2B  => "SPARC Version 9",
            0x2C  => "Siemens TriCore embedded processor",
            0x2D  => "Argonaut RISC Core",
            0x2E  => "Hitachi H8/300",
            0x2F  => "Hitachi H8/300H",
            0x30  => "Hitachi H8S",
            0x31  => "Hitachi H8/500",
            0x32  => "IA-64",
            0x33  => "Stanford MIPS-X",
            0x34  => "Motorola ColdFire",
            0x35  => "Motorola M68HC12",
            0x36  => "Fujitsu MMA Multimedia Accelerator",
            0x37  => "Siemens PCP",
            0x38  => "Sony nCPU embedded RISC processor",
            0x39  => "Denso NDR1 microprocessor",
            0x3A  => "Motorola Star*Core processor",
            0x3B  => "Toyota ME16 processor",
            0x3C  => "STMicroelectronics ST100 processor",
            0x3D  => "Advanced Logic Corp. TinyJ embedded processor family",
            0x3E  => "AMD x86-64",
            0x8C  => "TMS320C6000 Family",
            0xAF  => "MCST Elbrus e2k",
            0xB7  => "ARM 64-bits (ARMv8/Aarch64)",
            0xF3  => "RISC-V",
            0xF7  => "Berkeley Packet Filter",
            0x101 => "WDC 65C816",
            ][$i] )->map( $tag( 'architecture' ) )
        )
        ->map( $flatTags )
    ;

    print_r( $parser->run( $input, new ParserState ) );

