100 VDP(10)=148:CLEAR: IN$="101":SU=1:_NINIT:'start Fujinet
110 SCREEN0,0,0:COLOR15,0,0:WIDTH40:KEYOFF:POKE &HF3B1,26:'Allow 26 lines
120 L=0:C=0:CLS
130 U$="N:https://[server]/NOS-TT.php?page="+IN$+"-"+MID$(STR$(SU),2)
140 _NOPEN(U$,12,0):_NJSONPARSE(U$)
150 L$=MID$(STR$(C),2):C=C+1:IFC=25THEN 180
160 _NJSONQUERY(U$,"/lines/"+L$,F$,S%)
170 LOCATE0,C-1:PRINT F$:GOTO150
180 KN$="":LOCATE0,0:PRINT"###"
190 I$=INKEY$:IFI$=""THEN190
200 IF I$=">"THENSU=SU+1:GOTO120
210 IF I$="<"THENSU=SU-1:GOTO120
220 CH=ASC(I$):IFCH=27 THEN 260
230 IF CH >49 OR CH<58 THEN KN$=KN$+I$
240 LOCATE 0,0:PRINT MID$(KN$+"###",1,3)
250 IF LEN(KN$)=3THENIN$=KN$:SU=1:GOTO110ELSE190
260 CLS:POKE &HF3B1,24
270 PRINT "----------- Handleiding -----------"
280 PRINT
290 PRINT "De ### in de linker hoek is voor de
300 PRINT "de invoer van een pagina nummer.
310 PRINT "
320 PRINT "--------- Teletekst toetsen -------"
330 PRINT
340 PRINT " < Vorige Sub Page"
350 PRINT " > Volgende Sub Page"
360 PRINT
370 PRINT "-----------------------------------"
380 PRINT
390 PRINT "[0] Afsluiten"
400 PRINT "[1] Terug"
410 A$=INKEY$
420 IF A$="0"THEN END
430 IF A$="1" THEN RUN
440 GOTO 410

