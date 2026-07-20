100 REM EPSON PRINTER SAMPLE - MSX BASIC
110 REM Original: https://github.com/nwah/fn-printer-examples/blob/main/src/atari/printer.c
120 REM conversion done by grok
130 REM
140 SCREEN 0: WIDTH 40: CLS
150 PRINT "Choose sample:"
160 PRINT "1 = Plain text"
170 PRINT "2 = Epson styles + graphics"
180 INPUT A$
190 IF A$="1" THEN GOSUB 230
200 IF A$="2" THEN GOSUB 330
210 PRINT "Done!"
220 END
230 REM =====================
240 REM PLAIN TEXT SAMPLE
250 REM =====================
260 LPRINT CHR$(27);"@" : REM Reset
270 LPRINT
280 LPRINT "=============="
290 LPRINT "PRINTER SAMPLE"
300 LPRINT "=============="
310 LPRINT
320 LPRINT "Plain text"
330 REM =====================
340 REM EPSON STYLES + GRAPHICS
350 REM =====================
360 LPRINT CHR$(27);"@" : REM Reset
370 LPRINT "==================================="
380 LPRINT "PRINTER SAMPLE"
390 LPRINT
400 LPRINT "Epson Graphics & Text Styles"
410 LPRINT:LPRINT
420 LPRINT "------------------------------------"
430 LPRINT "PITCH"
440 LPRINT
450 LPRINT "Normal text"
460 LPRINT CHR$(15);"Compressed";CHR$(18)
470 LPRINT CHR$(27);"W";CHR$(1);"Expanded";CHR$(27);"W";CHR$(0)
480 LPRINT CHR$(27);"M";"Elite";CHR$(27);"P"
490 LPRINT CHR$(27);"p";CHR$(1);"Proportional";CHR$(27);"p";CHR$(0)
500 LPRINT:LPRINT
510 LPRINT "------------------------------------"
520 LPRINT "ENHANCEMENTS"
530 LPRINT
540 LPRINT CHR$(27);"E";"Emphasized";CHR$(27);"F"
550 LPRINT CHR$(27);"G";"Double stroke";CHR$(27);"H"
560 LPRINT CHR$(27);"-";CHR$(1);"Underline";CHR$(27);"-";CHR$(0)
570 LPRINT:LPRINT
580 LPRINT "------------------------------------"
590 LPRINT "GRAPHICS"
600 LPRINT
610 REM Single density graphics
620 LPRINT "Single density:"
630 LPRINT CHR$(27);"A";CHR$(8) : REM 8/72" spacing
640 FOR I=1 TO 3
650 LPRINT CHR$(27);"K";CHR$(128);CHR$(0); : REM 128 bytes wide
660 GOSUB 810 : REM Send pattern
670 LPRINT
680 NEXT I
690 LPRINT CHR$(27);"2" : REM Reset line spacing
700 REM Double density graphics
710 LPRINT "Double density:"
720 LPRINT CHR$(27);"A";CHR$(8)
730 FOR I=1 TO 3
740 LPRINT CHR$(27);"L";CHR$(128);CHR$(0);
750 GOSUB 810
760 LPRINT
770 NEXT I
780 LPRINT CHR$(27);"2"
790 LPRINT:LPRINT:LPRINT:LPRINT
800 RETURN
810 REM Send graphics pattern (0xEE,0xDD,0xBB,0x77 repeated)
820 FOR J=1 TO 32 : REM 128 bytes total
830 LPRINT CHR$(&HEE);CHR$(&HDD);CHR$(&HBB);CHR$(&H77);
840 NEXT J
850 RETURN
