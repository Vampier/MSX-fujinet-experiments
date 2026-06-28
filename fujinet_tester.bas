100 SCREEN 0,0,0:WIDTH 80:COLOR 3,0,0:KEY OFF
110 CLS
120 ON ERROR GOTO 520
130 '
140 'For more info visit:
150 ' https://github.com/nwah/msx-fujinet-basic/blob/main/README.md
160 '
170 'Let check if Fujinet is found
180 PRINT "Currently running"
190 CALL FUJINET
200 PRINT "Network info"
210 CALL FNCONFIG
220 GOSUB 570
230 PRINT "HOSTS"
240 '
250 'Load all 8 device slots from FujiNet into the local cache.
260 '
270 _FLOADHOSTSLOTS
280 FOR H=0 TO 7
290 '
300 'Read host slot URL from cache into name$.
310 '
320 _FGETHOSTSLOT(H, S$)
330 PRINT H; ":"; S$
340 NEXT H
350 PRINT "DEVICES"
360 '
370 ' Load all 8 device slots from FujiNet into the local cache.
380 '
390 _FLOADDEVSLOTS
400 FOR D=0 TO 7
410 '
420 'S% = host slot index for device slot.
430 '
440 _FGETDEVSLOTHOST(D, HS%)
450 '
460 ' file$ = filename for device slot.
470 '
480 _FGETDEVSLOTFILE(D, F$)
490 IF HS%=255 THEN PRINT D;" -- empty --" ELSE PRINT D; ":"; HS%; "/" ; F$
500 NEXT D
510 END
520 PRINT "Please check that fujinet is loaded in openMSX"
530 PRINT
540 PRINT "Bring up the console and use carta and cartb"
550 PRINT "cartb should contain the fujinet basic rom"
560 END
570 PRINT:PRINT "--- push space to continue ---":PRINT
580 A$=INKEY$
590 IF A$=CHR$(32) THEN RETURN ELSE 580

