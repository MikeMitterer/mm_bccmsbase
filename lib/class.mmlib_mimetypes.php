<?php

/**
 * If we don't have this function - we create it
 * 
 * More info here: http://at.php.net/mime_content_type
 */
if (!function_exists('mime_content_type')) {
   //debug('mime_content_type - does not exist. Please install the module: php5.0-mime-magic.');
   function mime_content_type ($f) {
       return trim(shell_exec('file -bi '.escapeshellarg($f)));
   }
}

//---------------------------------------------------------------
// MIME-Types are generated from the .ODT File in the DOC Folder
//---------------------------------------------------------------

$mimeTypes["aifc"][0]="audio/x-aiff";	$mimeTypes["aifc"][1]="AIFF-Sound-Dateien";
$mimeTypes["cdf"][0]="application/x-netcdf";	$mimeTypes["cdf"][1]="Unidata CDF-Dateien";
$mimeTypes["cht"][0]="audio/x-dspeeh";	$mimeTypes["cht"][1]="Sprachdateien";
$mimeTypes["class"][0]="application/octet-stream";	$mimeTypes["class"][1]="Ausf�hrbare Dateien";
$mimeTypes["com"][0]="application/octet-stream";	$mimeTypes["com"][1]="Ausf�hrbare Dateien";
$mimeTypes["dir"][0]="application/x-director";	$mimeTypes["dir"][1]="Macromedia Director-Dateien";
$mimeTypes["dll"][0]="application/octet-stream";	$mimeTypes["dll"][1]="Ausf�hrbare Dateien";
$mimeTypes["dot"][0]="application/msword";	$mimeTypes["dot"][1]="Microsoft Word Dateien";
$mimeTypes["dxr"][0]="application/x-director";	$mimeTypes["dxr"][1]="Macromedia Director-Dateien";
$mimeTypes["eps"][0]="application/postscript";	$mimeTypes["eps"][1]="Adobe PostScript-Dateien";
$mimeTypes["exe"][0]="application/octet-stream";	$mimeTypes["exe"][1]="Ausf�hrbare Dateien";
$mimeTypes["fh5"][0]="image/x-freehand";	$mimeTypes["fh5"][1]="Freehand-Dateien";
$mimeTypes["fhc"][0]="image/x-freehand";	$mimeTypes["fhc"][1]="Freehand-Dateien";
$mimeTypes["html"][0]="text/html";	$mimeTypes["html"][1]="HTML-Dateien";
$mimeTypes["jpe"][0]="image/jpeg";	$mimeTypes["jpe"][1]="JPEG-Dateien";
$mimeTypes["jpg"][0]="image/jpeg";	$mimeTypes["jpg"][1]="JPEG-Dateien";
$mimeTypes["midi"][0]="audio/x-midi";	$mimeTypes["midi"][1]="MIDI-Dateien";
$mimeTypes["mov"][0]="video/quicktime";	$mimeTypes["mov"][1]="Quicktime-Dateien";
$mimeTypes["mpe"][0]="video/mpeg";	$mimeTypes["mpe"][1]="MPEG-Dateien";
$mimeTypes["mpg"][0]="video/mpeg";	$mimeTypes["mpg"][1]="MPEG-Dateien";
$mimeTypes["phtml"][0]="application/x-httpd-php";	$mimeTypes["phtml"][1]="PHP-Dateien";
$mimeTypes["ps"][0]="application/postscript";	$mimeTypes["ps"][1]="Adobe PostScript-Dateien";
$mimeTypes["qd3"][0]="x-world/x-3dmf";	$mimeTypes["qd3"][1]="6DMF-Dateien";
$mimeTypes["qd3d"][0]="x-world/x-3dmf";	$mimeTypes["qd3d"][1]="5DMF-Dateien";
$mimeTypes["roff"][0]="application/x-troff";	$mimeTypes["roff"][1]="TROFF-Dateien (Unix)";
$mimeTypes["shtml"][0]="text/html";	$mimeTypes["shtml"][1]="HTML-Dateien";
$mimeTypes["spc"][0]="text/x-speech";	$mimeTypes["spc"][1]="Speech-Dateien";
$mimeTypes["sprite"][0]="application/x-sprite";	$mimeTypes["sprite"][1]="Sprite-Dateien";
$mimeTypes["texi"][0]="application/x-texinfo";	$mimeTypes["texi"][1]="Texinfo-Dateien";
$mimeTypes["tif"][0]="image/tiff";	$mimeTypes["tif"][1]="TIFF-Dateien";
$mimeTypes["tr"][0]="application/x-troff";	$mimeTypes["tr"][1]="TROFF-Dateien (Unix)";
$mimeTypes["troff"][0]="application/x-troff-me";	$mimeTypes["troff"][1]="TROFF-Dateien mit ME-Makros (Unix)";
$mimeTypes["vivo"][0]="video/vnd.vivo";	$mimeTypes["vivo"][1]="Vivo-Dateien";
$mimeTypes["xhtml"][0]="application/xhtml+xml";	$mimeTypes["xhtml"][1]="XHTML-Dateien";
$mimeTypes["3dm"][0]="x-world/x-3dmf";	$mimeTypes["3dm"][1]="4DMF-Dateien";
$mimeTypes["3dmf"][0]="x-world/x-3dmf";	$mimeTypes["3dmf"][1]="3DMF-Dateien";
$mimeTypes["ai"][0]="application/postscript";	$mimeTypes["ai"][1]="Adobe PostScript-Dateien";
$mimeTypes["aif"][0]="audio/x-aiff";	$mimeTypes["aif"][1]="AIFF-Sound-Dateien";
$mimeTypes["aiff"][0]="audio/x-aiff";	$mimeTypes["aiff"][1]="AIFF-Sound-Dateien";
$mimeTypes["asd"][0]="application/astound";	$mimeTypes["asd"][1]="Astound-Dateien";
$mimeTypes["asn"][0]="application/astound";	$mimeTypes["asn"][1]="Astound-Dateien";
$mimeTypes["au"][0]="audio/basic";	$mimeTypes["au"][1]="Sound-Dateien";
$mimeTypes["avi"][0]="video/x-msvideo";	$mimeTypes["avi"][1]="Microsoft AVI-Dateien";
$mimeTypes["bcpio"][0]="application/x-bcpio";	$mimeTypes["bcpio"][1]="BCPIO-Dateien";
$mimeTypes["bin"][0]="application/octet-stream";	$mimeTypes["bin"][1]="Ausf�hrbare Dateien";
$mimeTypes["cab"][0]="application/x-shockwave-flash";	$mimeTypes["cab"][1]="Flash Shockwave-Dateien";
$mimeTypes["chm"][0]="application/mshelp";	$mimeTypes["chm"][1]="Microsoft Windows Hilfe Dateien";
$mimeTypes["cod"][0]="image/cis-cod";	$mimeTypes["cod"][1]="CIS-Cod-Dateien";
$mimeTypes["cpio"][0]="application/x-cpio";	$mimeTypes["cpio"][1]="CPIO-Dateien";
$mimeTypes["csh"][0]="application/x-csh";	$mimeTypes["csh"][1]="C-Shellscript-Dateien";
$mimeTypes["css"][0]="text/css";	$mimeTypes["css"][1]="CSS Stylesheet-Dateien";
$mimeTypes["csv"][0]="text/comma-separated-values";	$mimeTypes["csv"][1]="kommaseparierte Datendateien";
$mimeTypes["dcr"][0]="application/x-director";	$mimeTypes["dcr"][1]="Macromedia Director-Dateien";
$mimeTypes["doc"][0]="application/msword";	$mimeTypes["doc"][1]="Microsoft Word Dateien";
$mimeTypes["dus"][0]="audio/x-dspeeh";	$mimeTypes["dus"][1]="Sprachdateien";
$mimeTypes["dvi"][0]="application/x-dvi";	$mimeTypes["dvi"][1]="DVI-Dateien";
$mimeTypes["dwf"][0]="drawing/x-dwf";	$mimeTypes["dwf"][1]="Drawing-Dateien";
$mimeTypes["dwg"][0]="application/acad";	$mimeTypes["dwg"][1]="AutoCAD-Dateien (nach NCSA)";
$mimeTypes["dxf"][0]="application/dxf";	$mimeTypes["dxf"][1]="AutoCAD-Dateien (nach CERN)";
$mimeTypes["es"][0]="audio/echospeech";	$mimeTypes["es"][1]="Echospeed-Dateien";
$mimeTypes["etx"][0]="text/x-setext";	$mimeTypes["etx"][1]="SeText-Dateien";
$mimeTypes["evy"][0]="application/x-envoy";	$mimeTypes["evy"][1]="Envoy-Dateien";
$mimeTypes["fh4"][0]="image/x-freehand";	$mimeTypes["fh4"][1]="Freehand-Dateien";
$mimeTypes["fif"][0]="image/fif";	$mimeTypes["fif"][1]="FIF-Dateien";
$mimeTypes["gif"][0]="image/gif";	$mimeTypes["gif"][1]="GIF-Dateien";
$mimeTypes["gtar"][0]="application/x-gtar";	$mimeTypes["gtar"][1]="GNU tar-Archivdateien";
$mimeTypes["gz"][0]="application/gzip";	$mimeTypes["gz"][1]="GNU Zip-Dateien";
$mimeTypes["hdf"][0]="application/x-hdf";	$mimeTypes["hdf"][1]="HDF-Dateien";
$mimeTypes["hlp"][0]="application/mshelp";	$mimeTypes["hlp"][1]="Microsoft Windows Hilfe Dateien";
$mimeTypes["hqx"][0]="application/mac-binhex40";	$mimeTypes["hqx"][1]="Macintosh Bin�rdateien";
$mimeTypes["htm"][0]="text/html";	$mimeTypes["htm"][1]="HTML-Dateien";
$mimeTypes["htm"][0]="application/xhtml+xml";	$mimeTypes["htm"][1]="XHTML-Dateien";
$mimeTypes["html"][0]="application/xhtml+xml";	$mimeTypes["html"][1]="XHTML-Dateien";
$mimeTypes["ief"][0]="image/ief";	$mimeTypes["ief"][1]="IEF-Dateien";
$mimeTypes["jpeg"][0]="image/jpeg";	$mimeTypes["jpeg"][1]="JPEG-Dateien";
$mimeTypes["js"][0]="text/javascript";	$mimeTypes["js"][1]="JavaScript-Dateien";
$mimeTypes["js"][0]="application/x-javascript";	$mimeTypes["js"][1]="serverseitige JavaScript-Dateien";
$mimeTypes["latex"][0]="application/x-latex";	$mimeTypes["latex"][1]="LaTeX-Quelldateien";
$mimeTypes["man"][0]="application/x-troff-man";	$mimeTypes["man"][1]="TROFF-Dateien mit MAN-Makros (Unix)";
$mimeTypes["mbd"][0]="application/mbedlet";	$mimeTypes["mbd"][1]="Mbedlet-Dateien";
$mimeTypes["mcf"][0]="image/vasa";	$mimeTypes["mcf"][1]="Vasa-Dateien";
$mimeTypes["me"][0]="application/x-troff-me";	$mimeTypes["me"][1]="TROFF-Dateien mit ME-Makros (Unix)";
$mimeTypes["mid"][0]="audio/x-midi";	$mimeTypes["mid"][1]="MIDI-Dateien";
$mimeTypes["mif"][0]="application/mif";	$mimeTypes["mif"][1]="FrameMaker Interchange Format Dateien";
$mimeTypes["movie"][0]="video/x-sgi-movie";	$mimeTypes["movie"][1]="Movie-Dateien";
$mimeTypes["mp2"][0]="audio/x-mpeg";	$mimeTypes["mp2"][1]="MPEG-Dateien";
$mimeTypes["mpeg"][0]="video/mpeg";	$mimeTypes["mpeg"][1]="MPEG-Dateien";
$mimeTypes["nc"][0]="application/x-netcdf";	$mimeTypes["nc"][1]="Unidata CDF-Dateien";
$mimeTypes["nsc"][0]="application/x-nschat";	$mimeTypes["nsc"][1]="NS Chat-Dateien";
$mimeTypes["oda"][0]="application/oda";	$mimeTypes["oda"][1]="Oda-Dateien";
$mimeTypes["pbm"][0]="image/x-portable-bitmap";	$mimeTypes["pbm"][1]="PBM Bitmap Dateien";
$mimeTypes["pdf"][0]="application/pdf";	$mimeTypes["pdf"][1]="Adobe PDF-Dateien";
$mimeTypes["pgm"][0]="image/x-portable-graymap";	$mimeTypes["pgm"][1]="PBM Graymap Dateien";
$mimeTypes["php"][0]="application/x-httpd-php";	$mimeTypes["php"][1]="PHP-Dateien";
$mimeTypes["png"][0]="image/png";	$mimeTypes["png"][1]="PNG-Dateien";
$mimeTypes["pnm"][0]="image/x-portable-anymap";	$mimeTypes["pnm"][1]="PBM Anymap Dateien";
$mimeTypes["pot"][0]="application/mspowerpoint";	$mimeTypes["pot"][1]="Microsoft Powerpoint Dateien";
$mimeTypes["ppm"][0]="image/x-portable-pixmap";	$mimeTypes["ppm"][1]="PBM Pixmap Dateien";
$mimeTypes["pps"][0]="application/mspowerpoint";	$mimeTypes["pps"][1]="Microsoft Powerpoint Dateien";
$mimeTypes["ppt"][0]="application/mspowerpoint";	$mimeTypes["ppt"][1]="Microsoft Powerpoint Dateien";
$mimeTypes["ppz"][0]="application/mspowerpoint";	$mimeTypes["ppz"][1]="Microsoft Powerpoint Dateien";
$mimeTypes["ptlk"][0]="application/listenup";	$mimeTypes["ptlk"][1]="Listenup-Dateien";
$mimeTypes["qt"][0]="video/quicktime";	$mimeTypes["qt"][1]="Quicktime-Dateien";
$mimeTypes["ra"][0]="audio/x-pn-realaudio";	$mimeTypes["ra"][1]="RealAudio-Dateien";
$mimeTypes["ram"][0]="audio/x-pn-realaudio";	$mimeTypes["ram"][1]="RealAudio-Dateien";
$mimeTypes["ras"][0]="image/cmu-raster";	$mimeTypes["ras"][1]="CMU-Raster-Dateien";
$mimeTypes["rgb"][0]="image/x-rgb";	$mimeTypes["rgb"][1]="RGB-Dateien";
$mimeTypes["rpm"][0]="audio/x-pn-realaudio-plugin";	$mimeTypes["rpm"][1]="RealAudio-Plugin-Dateien";
$mimeTypes["rtc"][0]="application/rtc";	$mimeTypes["rtc"][1]="RTC-Dateien";
$mimeTypes["rtf"][0]="text/rtf";	$mimeTypes["rtf"][1]="Microsoft RTF-Dateien";
$mimeTypes["rtf"][0]="application/rtf";	$mimeTypes["rtf"][1]="Microsoft RTF-Dateien";
$mimeTypes["rtx"][0]="text/richtext";	$mimeTypes["rtx"][1]="Richtext-Dateien";
$mimeTypes["sca"][0]="application/x-supercard";	$mimeTypes["sca"][1]="Supercard-Dateien";
$mimeTypes["sgm"][0]="text/x-sgml";	$mimeTypes["sgm"][1]="SGML-Dateien";
$mimeTypes["sgml"][0]="text/x-sgml";	$mimeTypes["sgml"][1]="SGML-Dateien";
$mimeTypes["sh"][0]="application/x-sh";	$mimeTypes["sh"][1]="Bourne Shellscript-Dateien";
$mimeTypes["shar"][0]="application/x-shar";	$mimeTypes["shar"][1]="Shell-Archivdateien";
$mimeTypes["sit"][0]="application/x-stuffit";	$mimeTypes["sit"][1]="Stuffit-Dateien";
$mimeTypes["smp"][0]="application/studiom";	$mimeTypes["smp"][1]="Studiom-Dateien";
$mimeTypes["snd"][0]="audio/basic";	$mimeTypes["snd"][1]="Sound-Dateien";
$mimeTypes["spl"][0]="application/futuresplash";	$mimeTypes["spl"][1]="Flash Futuresplash-Dateien";
$mimeTypes["spr"][0]="application/x-sprite";	$mimeTypes["spr"][1]="Sprite-Dateien";
$mimeTypes["src"][0]="application/x-wais-source";	$mimeTypes["src"][1]="WAIS Quelldateien";
$mimeTypes["stream"][0]="audio/x-qt-stream";	$mimeTypes["stream"][1]="Quicktime-Streaming-Dateien";
$mimeTypes["sv4cpio"][0]="application/x-sv4cpio";	$mimeTypes["sv4cpio"][1]="CPIO-Dateien";
$mimeTypes["sv4crc"][0]="application/x-sv4crc";	$mimeTypes["sv4crc"][1]="CPIO-Dateien mit CRC";
$mimeTypes["swf"][0]="application/x-shockwave-flash";	$mimeTypes["swf"][1]="Flash Shockwave-Dateien";
$mimeTypes["t"][0]="application/x-troff";	$mimeTypes["t"][1]="TROFF-Dateien (Unix)";
$mimeTypes["talk"][0]="text/x-speech";	$mimeTypes["talk"][1]="Speech-Dateien";
$mimeTypes["tar"][0]="application/x-tar";	$mimeTypes["tar"][1]="tar-Archivdateien";
$mimeTypes["tbk"][0]="application/toolbook";	$mimeTypes["tbk"][1]="Toolbook-Dateien";
$mimeTypes["tcl"][0]="application/x-tcl";	$mimeTypes["tcl"][1]="TCL Scriptdateien";
$mimeTypes["tex"][0]="application/x-tex";	$mimeTypes["tex"][1]="TeX-Dateien";
$mimeTypes["texinfo"][0]="application/x-texinfo";	$mimeTypes["texinfo"][1]="Texinfo-Dateien";
$mimeTypes["tiff"][0]="image/tiff";	$mimeTypes["tiff"][1]="TIFF-Dateien";
$mimeTypes["tsi"][0]="audio/tsplayer";	$mimeTypes["tsi"][1]="TS-Player-Dateien";
$mimeTypes["tsp"][0]="application/dsptype";	$mimeTypes["tsp"][1]="TSP-Dateien";
$mimeTypes["tsv"][0]="text/tab-separated-values";	$mimeTypes["tsv"][1]="tabulator-separierte Datendateien";
$mimeTypes["txt"][0]="text/plain";	$mimeTypes["txt"][1]="reine Textdateien";
$mimeTypes["ustar"][0]="application/x-ustar";	$mimeTypes["ustar"][1]="tar-Archivdateien (Posix)";
$mimeTypes["viv"][0]="video/vnd.vivo";	$mimeTypes["viv"][1]="Vivo-Dateien";
$mimeTypes["vmd"][0]="application/vocaltec-media-desc";	$mimeTypes["vmd"][1]="Vocaltec Mediadesc-Dateien";
$mimeTypes["vmf"][0]="application/vocaltec-media-file";	$mimeTypes["vmf"][1]="Vocaltec Media-Dateien";
$mimeTypes["vox"][0]="audio/voxware";	$mimeTypes["vox"][1]="Vox-Dateien";
$mimeTypes["vts"][0]="workbook/formulaone";	$mimeTypes["vts"][1]="FormulaOne-Dateien";
$mimeTypes["vtts"][0]="workbook/formulaone";	$mimeTypes["vtts"][1]="FormulaOne-Dateien";
$mimeTypes["wav"][0]="audio/x-wav";	$mimeTypes["wav"][1]="WAV-Dateien";
$mimeTypes["wbmp"][0]="image/vnd.wap.wbmp";	$mimeTypes["wbmp"][1]="Bitmap-Dateien (WAP)";
$mimeTypes["wml"][0]="text/vnd.wap.wml";	$mimeTypes["wml"][1]="WML-Dateien (WAP)";
$mimeTypes["wmlc"][0]="application/vnd.wap.wmlc";	$mimeTypes["wmlc"][1]="WMLC-Dateien (WAP)";
$mimeTypes["wmls"][0]="text/vnd.wap.wmlscript";	$mimeTypes["wmls"][1]="WML-Scriptdateien (WAP)";
$mimeTypes["wmlsc"][0]="application/vnd.wap.wmlscriptc";	$mimeTypes["wmlsc"][1]="WML-Script-C-dateien (WAP)";
$mimeTypes["wrl"][0]="model/vrml)";	$mimeTypes["wrl"][1]="Visualisierung virtueller Welten (VRML)";
$mimeTypes["xbm"][0]="image/x-xbitmap";	$mimeTypes["xbm"][1]="XBM-Dateien";
$mimeTypes["xla"][0]="application/msexcel";	$mimeTypes["xla"][1]="Microsoft Excel Dateien";
$mimeTypes["xls"][0]="application/msexcel";	$mimeTypes["xls"][1]="Microsoft Excel Dateien";
$mimeTypes["xml"][0]="application/xml";	$mimeTypes["xml"][1]="XML-Dateien";
$mimeTypes["xpm"][0]="image/x-xpixmap";	$mimeTypes["xpm"][1]="XPM-Dateien";
$mimeTypes["xwd"][0]="image/x-windowdump";	$mimeTypes["xwd"][1]="X-Windows Dump";
$mimeTypes["z"][0]="application/x-compress";	$mimeTypes["z"][1]="zlib-komprimierte Dateien";
$mimeTypes["zip"][0]="application/zip";	$mimeTypes["zip"][1]="ZIP-Archivdateien";

?>