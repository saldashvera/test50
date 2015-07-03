<?
# Futallaby 031222
#
# For setup instructions and latest version, please visit:
# http://www.mapored.com/futallaby/
#
# Based on GazouBBS and Futaba

include "config.php";
include "strings_e.php";		//String resource file


extract($_POST);
extract($_GET);
extract($_COOKIE);
$upfile_name=$_FILES["upfile"]["name"];
$upfile=$_FILES["upfile"]["tmp_name"];

$path = realpath("./").'/'.IMG_DIR;
ignore_user_abort(TRUE);
$badstring = array("dummy_string","dummy_string2"); // Refused text
$badfile = array("dummy","dummy2"); //Refused files (md5 hashes)

$badip = array("addr1\\.dummy\\.com","addr2\\.dummy\\.com"); //Refused hosts (IP bans)

if(!$con=mysql_connect(SQLHOST,SQLUSER,SQLPASS)){
  echo S_SQLCONF;	//unable to connect to DB (wrong user/pass?)
  exit;
}

$db_id=mysql_select_db(SQLDB,$con);
  if(!$db_id){echo S_SQLDBSF;}

if (!table_exist(SQLLOG)) {
  echo (SQLLOG.S_TCREATE);
  $result = mysql_call("create table ".SQLLOG." (primary key(no),
    no    int not null auto_increment,
    now   text,
    sub   text,
    com   text,
    host  text,
    ext   text,
    w     int,
    h     int,
    tim   text,
    time  int,
    md5   text,
    fsize int,
    root  timestamp,
    resto int)");
  if(!$result){echo S_TCREATEF;}
}

function updatelog($resno=0){
  global $path;

  $find = false;
  $resno=(int)$resno;
  if($resno){
    $result = mysql_call("select * from ".SQLLOG." where root>0 and no=$resno");
    if($result){
      $find = mysql_fetch_row($result);
      mysql_free_result($result);
    }
    if(!$find) error(S_REPORTERR);
  }
  if($resno){
    if(!$treeline=mysql_call("select * from ".SQLLOG." where root>0 and no=".$resno." order by root desc")){echo S_SQLFAIL;}
  }else{
    if(!$treeline=mysql_call("select * from ".SQLLOG." where root>0 order by root desc")){echo S_SQLFAIL;}
  }

  //Finding the last entry number
  if(!$result=mysql_call("select max(no) from ".SQLLOG)){echo S_SQLFAIL;}
  $row=mysql_fetch_array($result);
  $lastno=(int)$row[0];
  mysql_free_result($result);

  $counttree=mysql_num_rows($treeline);
  if(!$counttree){
    $logfilename=PHP_SELF2;
    $dat='';
    head($dat);
    form($dat,$resno);
    $fp = fopen($logfilename, "w");
    set_file_buffer($fp, 0);
    rewind($fp);
    fputs($fp, $dat);
    fclose($fp);
    chmod($logfilename,0666);
  }
  for($page=0;$page<$counttree;$page+=PAGE_DEF){
    $dat='';
    head($dat);
    form($dat,$resno);
    if(!$resno){
      $st = $page;
    }
    $dat.='<form action="'.PHP_SELF.'" method=POST>';

  for($i = $st; $i < $st+PAGE_DEF; $i++){
    list($no,$now,$name,$email,$sub,$com,$host,$pwd,$ext,$w,$h,$tim,$time,$md5,$fsize,)=mysql_fetch_row($treeline);
    if(!$no){break;}

    // URL and link
    if($email) $name = "<a href=\"mailto:$email\">$name</a>";
    $com = auto_link($com);
    $com = eregi_replace("(^|>)(&gt;[^<]*)", "\\1<font color=".RE_COL.">\\2</font>", $com);
    // Picture file name
    $img = $path.$tim.$ext;
    $src = IMG_DIR.$tim.$ext;
    // img tag creation
    $imgsrc = "";
    if($ext){
      $size = $fsize;//file size displayed in alt text
      if($w && $h){//when there is size...
        if(@is_file(THUMB_DIR.$tim.'s.jpg')){
          $imgsrc = "<small>".S_THUMB."</small><br><a href=\"".$src."\" target=_blank><img src=".THUMB_DIR.$tim.'s.jpg'.
      " border=0 align=left width=$w height=$h hspace=20 alt=\"".$size." B\"></a>";
        }else{
          $imgsrc = "<a href=\"".$src."\" target=_blank><img src=".$src.
      " border=0 align=left width=$w height=$h hspace=20 alt=\"".$size." B\"></a>";
        }
      }else{
        $imgsrc = "<a href=\"".$src."\" target=_blank><img src=".$src.
      " border=0 align=left hspace=20 alt=\"".$size." B\"></a>";
      }
      $dat.=S_PICNAME."<a href=\"$src\" target=_blank>$tim$ext</a>-($size B)<br>$imgsrc";
    }
    //  Main creation
    $dat.="<input type=checkbox name=\"$no\" value=delete><font color=#cc1105 size=+1><b>$sub</b></font> \n";
    $dat.="Name <font color=#117743><b>$name</b></font> $now No.$no &nbsp; \n";
    if(!$resno) $dat.="[<a href=".PHP_SELF."?res=$no>".S_REPLY."</a>]";
    $dat.="\n<blockquote>$com</blockquote>";

     // Deletion pending
     if($lastno-LOG_MAX*0.95>$no){
      $dat.="<font color=\"#f00000\"><b>".S_OLD."</b></font><br>\n";
     }

    if(!$resline=mysql_call("select * from ".SQLLOG." where resto=".$no." order by no")){echo S_SQLFAIL;}
    $countres=mysql_num_rows($resline);

    if(!$resno){
     $s=$countres - 10;
     if($s<0){$s=0;}
     elseif($s>0){
      $dat.="<font color=\"#707070\">".S_RESU.$s.S_ABBR."</font><br>\n";
     }
    }else{$s=1;}

    while($resrow=mysql_fetch_row($resline)){
      if($s>0){$s--;continue;}
      list($no,$now,$name,$email,$sub,$com,$host,$pwd,$ext,$w,$h,$tim,$time,$md5,$fsize,)=$resrow;
      if(!$no){break;}

      // URL and e-mail
      if($email) $name = "<a href=\"mailto:$email\">$name</a>";
      $com = auto_link($com);
      $com = eregi_replace("(^|>)(&gt;[^<]*)", "\\1<font color=".RE_COL.">\\2</font>", $com);
      // Main creation
      $dat.="<table border=0><tr><td nowrap align=right valign=top>&gt;&gt;</td><td bgcolor=#F0E0D6>\n";
      $dat.="<input type=checkbox name=\"$no\" value=delete><font color=#cc1105 size=+1><b>$sub</b></font> \n";
      $dat.="Name <font color=#117743><b>$name</b></font> $now No.$no &nbsp; \n";
      $dat.="<blockquote>$com</blockquote>";
      $dat.="</td></tr></table>\n";
    }
    $dat.="<br clear=left><hr>\n";
    clearstatcache();//clear stat cache of a file
    mysql_free_result($resline);
    $p++;
    if($resno){break;} //only one tree line at time of res
  }
$dat.='<table align=right><tr><td nowrap align=center>
<input type=hidden name=mode value=usrdel>'.S_REPDEL.'[<input type=checkbox name=onlyimgdel value=on>'.S_DELPICONLY.']<br>
'.S_DELKEY.'<input type=password name=pwd size=8 maxlength=8 value="">
<input type=submit value="'.S_DELETE.'"></form></td></tr></table>
<script language="JavaScript"><!--
l();
//--></script>';

    if(!$resno){ // if not in res display mode
      $prev = $st - PAGE_DEF;
      $next = $st + PAGE_DEF;
    //  Page processing
      $dat.="<table align=left border=1><tr>";
      if($prev >= 0){
        if($prev==0){
          $dat.="<form action=\"".PHP_SELF2."\" method=get><td>";
        }else{
          $dat.="<form action=\"".$prev/PAGE_DEF.PHP_EXT."\" method=get><td>";
        }
        $dat.="<input type=submit value=\"".S_PREV."\">";
        $dat.="</td></form>";
      }else{$dat.="<td>".S_FIRSTPG."</td>";}

      $dat.="<td>";
      for($i = 0; $i < $counttree ; $i+=PAGE_DEF){
        if($i&&!($i%(PAGE_DEF*2))){$dat.="<br>";}
        if($st==$i){$dat.="[<b>".($i/PAGE_DEF)."</b>] ";}
        else{
          if($i==0){$dat.="[<a href=\"".PHP_SELF2."\">0</a>] ";}
          else{$dat.="[<a href=\"".($i/PAGE_DEF).PHP_EXT."\">".($i/PAGE_DEF)."</a>] ";}
        }
      }
      $dat.="</td>";

      if($p >= PAGE_DEF && $counttree > $next){
        $dat.="<form action=\"".$next/PAGE_DEF.PHP_EXT."\" method=get><td>";
        $dat.="<input type=submit value=\"".S_NEXT."\">";
        $dat.="</td></form>";
      }else{$dat.="<td>".S_LASTPG."</td>";}
        $dat.="</tr></table><br clear=all>\n";
    }
    foot($dat);
    if($resno){echo $dat;break;}
    if($page==0){$logfilename=PHP_SELF2;}
    else{$logfilename=$page/PAGE_DEF.PHP_EXT;}
    $fp = fopen($logfilename, "w");
    set_file_buffer($fp, 0);
    rewind($fp);
    fputs($fp, $dat);
    fclose($fp);
    chmod($logfilename,0666);
  }
  mysql_free_result($treeline);
}


function mysql_call($query){
  $ret=mysql_query($query);
  if(!$ret){
#echo "error!!<br>";
    echo $query."<br>";
#    echo mysql_errno().": ".mysql_error()."<br>";
  }
  return $ret;
}

/* head */
function head(&$dat){
$titlepart = '';
if (SHOWTITLEIMG == 1) {
	$titlepart.= '<img src='.TITLEIMG.' alt='.TITLE.'>';
	if (SHOWTITLETXT == 1) {$titlepart.= '<br>';}
} else if (SHOWTITLEIMG == 2) {
	$titlepart.= '<img src='.TITLEIMG.' onclick="this.src=this.src;">';
	if (SHOWTITLETXT == 1) {$titlepart.= '<br>';}
}
if (SHOWTITLETXT == 1) {
	$titlepart.= '<font color="#800000" size=5><b><SPAN>'.TITLE.'</SPAN></b></font>';
}
  $dat.='<html><head>
<META HTTP-EQUIV="content-type" CONTENT="text/html; charset=shift-jis">
<!-- meta HTTP-EQUIV="pragma" CONTENT="no-cache" -->
<STYLE TYPE="text/css">
<!--
body,tr,td,th { font-size:12pt }
a:hover { color:#DD0000; }
span { font-size:20pt }
small { font-size:10pt }
-->
</STYLE>
<title>'.TITLE.'</title>
<script language="JavaScript"><!--
function l(e){var P=getCookie("pwdc"),N=getCookie("namec"),i;with(document){for(i=0;i<forms.length;i++){if(forms[i].pwd)with(forms[i]){if(!pwd.value)pwd.value=P;}if(forms[i].name)with(forms[i]){if(!name.value)name.value=N;}}}};function getCookie(key, tmp1, tmp2, xx1, xx2, xx3) {tmp1 = " " + document.cookie + ";";xx1 = xx2 = 0;len = tmp1.length;	while (xx1 < len) {xx2 = tmp1.indexOf(";", xx1);tmp2 = tmp1.substring(xx1 + 1, xx2);xx3 = tmp2.indexOf("=");if (tmp2.substring(0, xx3) == key) {return(unescape(tmp2.substring(xx3 + 1, xx2 - xx1 - 1)));}xx1 = xx2 + 1;}return("");}
//--></script>
</head>
<body bgcolor="#FFFFEE" text="#800000" link="#0000EE" vlink="#0000EE">
<p align=right>
[<a href="'.HOME.'" target="_top">'.S_HOME.'</a>]
[<a href="'.PHP_SELF.'?mode=admin">'.S_ADMIN.'</a>]
<p align=center>'.$titlepart.'<hr width="90%" size=1>';
}
/* Contribution form */
function form(&$dat,$resno,$admin=""){
  $maxbyte = MAX_KB * 1024;
  $no=$resno;
  if($resno){
    $msg .= "[<a href=\"".PHP_SELF2."\">".S_RETURN."</a>]\n";
    $msg .= "<table width='100%'><tr><th bgcolor=#e04000>\n";
    $msg .= "<font color=#FFFFFF>".S_POSTING."</font>\n";
    $msg .= "</th></tr></table>\n";
  }
  if($admin){
    $hidden = "<input type=hidden name=admin value=\"".ADMIN_PASS."\">";
    $msg = "<h4>".S_NOTAGS."</h4>";
  }
  $dat.=$msg.'<center>
<form action="'.PHP_SELF.'" method="POST" enctype="multipart/form-data">
<input type=hidden name=mode value="regist">
'.$hidden.'
<input type=hidden name="MAX_FILE_SIZE" value="'.$maxbyte.'">
';
if($no){$dat.='<input type=hidden name=resto value="'.$no.'">
';}
$dat.='<table cellpadding=1 cellspacing=1>
<tr><td bgcolor=#eeaa88><b>'.S_NAME.'</b></td><td><input type=text name=name size="28"></td></tr>
<tr><td bgcolor=#eeaa88><b>'.S_EMAIL.'</b></td><td><input type=text name=email size="28"></td></tr>
<tr><td bgcolor=#eeaa88><b>'.S_SUBJECT.'</b></td><td><input type=text name=sub size="35">
<input type=submit value="'.S_SUBMIT.'"></td></tr>
<tr><td bgcolor=#eeaa88><b>'.S_COMMENT.'</b></td><td><textarea name=com cols="48" rows="4" wrap=soft></textarea></td></tr>
';
if(!$resno){
$dat.='<tr><td bgcolor=#eeaa88><b>'.S_UPLOADFILE.'</b></td>
<td><input type=file name=upfile size="35">
[<label><input type=checkbox name=textonly value=on>'.S_NOFILE.'</label>]</td></tr>
';}
$dat.='<tr><td bgcolor=#eeaa88><b>'.S_DELPASS.'</b></td><td><input type=password name=pwd size=8 maxlength=8 value=""><small>'.S_DELEXPL.'</small></td></tr>
<tr><td colspan=2>
<small>'.S_RULES.'</small></td></tr></table></form></center><hr>';
}

/* Footer */
function foot(&$dat){
  $dat.='
<center>
<small>'.S_FOOT.'
</small>
</center>
</body></html>';
}
function error($mes,$dest=''){
  global $upfile_name,$path;
  if(is_file($dest)) unlink($dest);
  head($dat);
  echo $dat;
  echo "<br><br><hr size=1><br><br>
        <center><font color=red size=5><b>$mes<br><br><a href=".PHP_SELF2.">".S_RELOAD."</a></b></font></center>
        <br><br><hr size=1>";
  die("</body></html>");
}
/* Auto Linker */
function auto_link($proto){
  $proto = ereg_replace("(https?|ftp|news)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)","<a href=\"\\1\\2\" target=\"_blank\">\\1\\2</a>",$proto);
  return $proto;
}

function  proxy_connect($port) {
  $fp = @fsockopen ($_SERVER["REMOTE_ADDR"], $port,$a,$b,2);
  if(!$fp){return 0;}else{return 1;}
}
/* Regist */
function regist($name,$email,$sub,$com,$url,$pwd,$upfile,$upfile_name,$resto){
  global $path,$badstring,$badfile,$badip,$pwdc,$textonly;

  // time
  $time = time();
  $tim = $time.substr(microtime(),2,3);

  // upload processing
  if($upfile&&file_exists($upfile)){
    $dest = $path.$tim.'.tmp';
    move_uploaded_file($upfile, $dest);
    //if an error in up, it changes to down (what?)
    //copy($upfile, $dest);
    $upfile_name = CleanStr($upfile_name);
    if(!is_file($dest)) error(S_UPFAIL,$dest);
    $size = getimagesize($dest);
    if(!is_array($size)) error(S_NOREC,$dest);
    $md5 = md5_of_file($dest);
    foreach($badfile as $value){if(ereg("^$value",$md5)){
      error(S_SAMEPIC,$dest); //Refuse this image
    }}
    chmod($dest,0666);
    $W = $size[0];
    $H = $size[1];
    $fsize = filesize($dest);
    if($fsize>MAX_KB * 1024) error(S_TOOBIG,$dest);
    switch ($size[2]) {
      case 1 : $ext=".gif";break;
      case 2 : $ext=".jpg";break;
      case 3 : $ext=".png";break;
      case 4 : $ext=".swf";break;
      case 5 : $ext=".psd";break;
      case 6 : $ext=".bmp";break;
      case 13 : $ext=".swf";break;
      default : $ext=".xxx";break;
    }

    // Picture reduction
    if($W > MAX_W || $H > MAX_H){
      $W2 = MAX_W / $W;
      $H2 = MAX_H / $H;
      ($W2 < $H2) ? $key = $W2 : $key = $H2;
      $W = ceil($W * $key);
      $H = ceil($H * $key);
    }
    $mes = S_UPGOOD;
  }

  if($_FILES["upfile"]["error"]==2){
    error(S_TOOBIG,$dest);
  }
  if($upfile_name&&$_FILES["upfile"]["size"]==0){
    error(S_TOOBIGORNONE,$dest);
  }

  //The last result number
  if(!$result=mysql_call("select max(no) from ".SQLLOG)){echo S_SQLFAIL;}
  $row=mysql_fetch_array($result);
  $lastno=(int)$row[0];
  mysql_free_result($result);

  // Number of log lines
  if(!$result=mysql_call("select no,ext,tim from ".SQLLOG." where no<=".($lastno-LOG_MAX))){echo S_SQLFAIL;}
  else{
    while($resrow=mysql_fetch_row($result)){
      list($dno,$dext,$dtim)=$resrow;
      if(!mysql_call("delete from ".SQLLOG." where no=".$dno)){echo S_SQLFAIL;}
      if($dext){
        if(is_file($path.$dtim.$dext)) unlink($path.$dtim.$dext);
        if(is_file(THUMB_DIR.$dtim.'s.jpg')) unlink(THUMB_DIR.$dtim.'s.jpg');
      }
    }
    mysql_free_result($result);
  }

  $find = false;
  $resto=(int)$resto;
  if($resto){
    if(!$result = mysql_call("select * from ".SQLLOG." where root>0 and no=$resto")){echo S_SQLFAIL;}
    else{
      $find = mysql_fetch_row($result);
      mysql_free_result($result);
    }
    if(!$find) error(S_NOTHREADERR,$dest);
  }

  foreach($badstring as $value){if(ereg($value,$com)||ereg($value,$sub)||ereg($value,$name)||ereg($value,$email)){
  error(S_STRREF,$dest);};}
  if($_SERVER["REQUEST_METHOD"] != "POST") error(S_UNJUST,$dest);
  // Form content check
  if(!$name||ereg("^[ |�@|]*$",$name)) $name="";
  if(!$com||ereg("^[ |�@|\t]*$",$com)) $com="";
  if(!$sub||ereg("^[ |�@|]*$",$sub))   $sub="";

  if(!$resto&&!$textonly&&!is_file($dest)) error(S_NOPIC,$dest);
  if(!$com&&!is_file($dest)) error(S_NOTEXT,$dest);

 $name=ereg_replace(S_MANAGEMENT,"\"".S_MANAGEMENT."\"",$name);
 $name=ereg_replace(S_DELETION,"\"".S_DELETION."\"",$name);

if(strlen($com) > 1000) error(S_TOOLONG,$dest);
if(strlen($name) > 100) error(S_TOOLONG,$dest);
if(strlen($sub) > 100) error(S_TOOLONG,$dest);
if(strlen($resto) > 10) error(S_UNUSUAL,$dest);
if(strlen($url) > 10) error(S_UNUSUAL,$dest);

  //host check
  $host = gethostbyaddr($_SERVER["REMOTE_ADDR"]);

  foreach($badip as $value){ //Refusal hosts
   if(eregi("$value$",$host)){
    error(S_BADHOST,$dest);
  }}
  if(eregi("^mail",$host)
    || eregi("^ns",$host)
    || eregi("^dns",$host)
    || eregi("^ftp",$host)
    || eregi("^prox",$host)
    || eregi("^pc",$host)
    || eregi("^[^\.]\.[^\.]$",$host)){
    $pxck = "on";
  }
  if(eregi("ne\\.jp$",$host)||
    eregi("ad\\.jp$",$host)||
    eregi("bbtec\\.net$",$host)||
    eregi("aol\\.com$",$host)||
    eregi("uu\\.net$",$host)||
    eregi("asahi-net\\.or\\.jp$",$host)||
    eregi("rim\\.or\\.jp$",$host)
    ){$pxck = "off";}
  else{$pxck = "on";}

  if($pxck=="on" && PROXY_CHECK){
    if(proxy_connect('80') == 1){
      error(S_PROXY80,$dest);
    } elseif(proxy_connect('8080') == 1){
      error(S_PROXY8080,$dest);
    }
  }

  // No, path, time, and url format
  srand((double)microtime()*1000000);
  if($pwd==""){
    if($pwdc==""){
      $pwd=rand();$pwd=substr($pwd,0,8);
    }else{
      $pwd=$pwdc;
    }
  }

  $c_pass = $pwd;
  $pass = ($pwd) ? substr(md5($pwd),2,8) : "*";
 $youbi = array(S_SUN, S_MON, S_TUE, S_WED, S_THU, S_FRI, S_SAT);
  $yd = $youbi[gmdate("w", $time+9*60*60)] ;
  $now = gmdate("y/m/d",$time+9*60*60)."(".(string)$yd.")".gmdate("H:i",$time+9*60*60);
  if(DISP_ID){
    if($email&&DISP_ID==1){
      $now .= " ID:???";
    }else{
      $now.=" ID:".substr(crypt(md5($_SERVER["REMOTE_ADDR"].'id'.gmdate("Ymd", $time+9*60*60)),'id'),-8);
    }
  }
  //Text plastic surgery (rorororor)
  $email= CleanStr($email);  $email=ereg_replace("[\r\n]","",$email);
  $sub  = CleanStr($sub);    $sub  =ereg_replace("[\r\n]","",$sub);
  $url  = CleanStr($url);    $url  =ereg_replace("[\r\n]","",$url);
  $resto= CleanStr($resto);  $resto=ereg_replace("[\r\n]","",$resto);
  $com  = CleanStr($com);
  // Standardize new character lines
  $com = str_replace( "\r\n",  "\n", $com);
  $com = str_replace( "\r",  "\n", $com);
  // Continuous lines
  $com = ereg_replace("\n((�@| )*\n){3,}","\n",$com);
  if(!BR_CHECK || substr_count($com,"\n")<BR_CHECK){
    $com = nl2br($com);		//br is substituted before newline char
  }
  $com = str_replace("",  "", $com);	//\n is erased

  $name=ereg_replace("","",$name);  //what the heck?
  $name=ereg_replace("[\r\n]","",$name);
  $names=$name;
  $name = CleanStr($name);
  if(ereg("(#|��)(.*)",$names,$regs)){
    $cap = $regs[2];
    $cap=strtr($cap,"&amp;", "&");
    $cap=strtr($cap,"&#44;", ",");
    $name=ereg_replace("(#|��)(.*)","",$name);
    $salt=substr($cap."H.",1,2);
    $salt=ereg_replace("[^\.-z]",".",$salt);
    $salt=strtr($salt,":;<=>?@[\\]^_`","ABCDEFGabcdef");
    $name.=" </b>��".substr(crypt($cap,$salt),-10)."<b>";
  }

 if(!$name) $name=S_ANONAME;
 if(!$com) $com=S_ANOTEXT;
 if(!$sub) $sub=S_ANOTITLE;

  // Read the log
  $query="select time from ".SQLLOG." where com='".mysql_escape_string($com)."' ".
         "and host='".mysql_escape_string($host)."' ".
         "and no>".($lastno-20);  //the same
  if(!$result=mysql_call($query)){echo S_SQLFAIL;}
  $row=mysql_fetch_array($result);
  mysql_free_result($result);
  if($row&&!$upfile_name)error(S_RENZOKU3,$dest);

  $query="select time from ".SQLLOG." where time>".($time - RENZOKU)." ".
         "and host='".mysql_escape_string($host)."' ";  //from precontribution
  if(!$result=mysql_call($query)){echo S_SQLFAIL;}
  $row=mysql_fetch_array($result);
  mysql_free_result($result);
  if($row&&!$upfile_name)error(S_RENZOKU3, $dest);

  // Upload processing
  if($dest&&file_exists($dest)){

  $query="select time from ".SQLLOG." where time>".($time - RENZOKU2)." ".
         "and host='".mysql_escape_string($host)."' ";  //from precontribution
  if(!$result=mysql_call($query)){echo S_SQLFAIL;}
  $row=mysql_fetch_array($result);
  mysql_free_result($result);
  if($row&&$upfile_name)error(S_RENZOKU2,$dest);

  //Duplicate image check
    $result = mysql_call("select tim,ext,md5 from ".SQLLOG." where md5='".$md5."'");
    if($result){
      list($timp,$extp,$md5p) = mysql_fetch_row($result);
      mysql_free_result($result);
#      if($timp&&file_exists($path.$timp.$extp)){ #}
      if($timp){
        error(S_DUPE,$dest);
      }
    }
  }

  $restoqu=(int)$resto;
  if($resto){ //res,root processing
    $rootqu="0";
    if(!$resline=mysql_call("select * from ".SQLLOG." where resto=".$resto)){echo S_SQLFAIL;}
    $countres=mysql_num_rows($resline);
    mysql_free_result($resline);
    if(!stristr($email,'sage') && $countres < MAX_RES){
      $query="update ".SQLLOG." set root=now() where no=$resto"; //age
      if(!$result=mysql_call($query)){echo S_SQLFAIL;}
    }
  }else{$rootqu="now()";} //now it is root

  $query="insert into ".SQLLOG." (now,name,email,sub,com,host,pwd,ext,w,h,tim,time,md5,fsize,root,resto) values (".
"'".$now."',".
"'".mysql_escape_string($name)."',".
"'".mysql_escape_string($email)."',".
"'".mysql_escape_string($sub)."',".
"'".mysql_escape_string($com)."',".
"'".mysql_escape_string($host)."',".
"'".mysql_escape_string($pass)."',".
"'".$ext."',".
(int)$W.",".
(int)$H.",".
"'".$tim."',".
(int)$time.",".
"'".$md5."',".
(int)$fsize.",".
$rootqu.",".
(int)$resto.")";
  if(!$result=mysql_call($query)){echo S_SQLFAIL;}  //post registration

    //Cookies
  setcookie ("pwdc", $c_pass,time()+7*24*3600);  /* 1 week cookie expiration */
  if(function_exists("mb_internal_encoding")&&function_exists("mb_convert_encoding")
      &&function_exists("mb_substr")){
    if(ereg("MSIE|Opera",$_SERVER["HTTP_USER_AGENT"])){
      $i=0;$c_name='';
      mb_internal_encoding("ES");
      while($j=mb_substr($names,$i,1)){
        $j = mb_convert_encoding($j, "UTF-8" );
        $c_name.="%u".bin2hex($j);
        $i++;
      }


  if($dest&&file_exists($dest)){
    rename($dest,$path.$tim.$ext);
    if(USE_THUMB){thumb($path,$tim,$ext);}
  }
  updatelog();

  echo "<html><head><META HTTP-EQUIV=\"refresh\" content=\"1;URL=".PHP_SELF2."\"></head>";
  echo "<body>$mes ".S_SCRCHANGE."</body></html>";
}

//thumbnails
function thumb($path,$tim,$ext){
  if(!function_exists("ImageCreate")||!function_exists("ImageCreateFromJPEG"))return;
  $fname=$path.$tim.$ext;
  $thumb_dir = THUMB_DIR;     //thumbnail directory
  $width     = MAX_W;            //output width
  $height    = MAX_H;            //output height
  // width, height, and type are aquired
  $size = GetImageSize($fname);
  switch ($size[2]) {
    case 1 :
      if(function_exists("ImageCreateFromGIF")){
        $im_in = @ImageCreateFromGIF($fname);
        if($im_in){break;}
      }
      if(!is_executable(realpath("./gif2png"))||!function_exists("ImageCreateFromPNG"))return;
      @exec(realpath("./gif2png")." $fname",$a);
      if(!file_exists($path.$tim.'.png'))return;
      $im_in = @ImageCreateFromPNG($path.$tim.'.png');
      unlink($path.$tim.'.png');
      if(!$im_in)return;
      break;
    case 2 : $im_in = @ImageCreateFromJPEG($fname);
      if(!$im_in){return;}
       break;
    case 3 :
      if(!function_exists("ImageCreateFromPNG"))return;
      $im_in = @ImageCreateFromPNG($fname);
      if(!$im_in){return;}
      break;
    default : return;
  }
  // Resizing
  if ($size[0] > $width || $size[1] >$height) {
    $key_w = $width / $size[0];
    $key_h = $height / $size[1];
    ($key_w < $key_h) ? $keys = $key_w : $keys = $key_h;
    $out_w = ceil($size[0] * $keys) +1;
    $out_h = ceil($size[1] * $keys) +1;
  } else {
    $out_w = $size[0];
    $out_h = $size[1];
  }
  // the thumbnail is created
  if(function_exists("ImageCreateTrueColor")&&get_gd_ver()=="2"){
    $im_out = ImageCreateTrueColor($out_w, $out_h);
  }else{$im_out = ImageCreate($out_w, $out_h);}
  // copy resized original
  ImageCopyResized($im_out, $im_in, 0, 0, 0, 0, $out_w, $out_h, $size[0], $size[1]);
  // thumbnail saved
  ImageJPEG($im_out, $thumb_dir.$tim.'s.jpg',60);
  chmod($thumb_dir.$tim.'s.jpg',0666);
  // created image is destroyed
  ImageDestroy($im_in);
  ImageDestroy($im_out);
}
//check version of gd
function get_gd_ver(){
  if(function_exists("gd_info")){
    $gdver=gd_info();
    $phpinfo=$gdver["GD Version"];
  }else{ //earlier than php4.3.0
    ob_start();
    phpinfo(8);
    $phpinfo=ob_get_contents();
    ob_end_clean();
    $phpinfo=strip_tags($phpinfo);
    $phpinfo=stristr($phpinfo,"gd version");
    $phpinfo=stristr($phpinfo,"version");
  }
  $end=strpos($phpinfo,".");
  $phpinfo=substr($phpinfo,0,$end);
  $length = strlen($phpinfo)-1;
  $phpinfo=substr($phpinfo,$length);
  return $phpinfo;
}
//md5 calculation for earlier than php4.2.0
function md5_of_file($inFile) {
 if (file_exists($inFile)){
  if(function_exists('md5_file')){
    return md5_file($inFile);
  }else{
    $fd = fopen($inFile, 'r');
    $fileContents = fread($fd, filesize($inFile));
    fclose ($fd);
    return md5($fileContents);
  }
 }else{
  return false;
}}
/* text plastic surgery */
function CleanStr($str){
  global $admin;
  $str = trim($str);//blankspace removal
  if (get_magic_quotes_gpc()) {//magic quotes is deleted (?)
    $str = stripslashes($str);
  }
  if($admin!=ADMIN_PASS){//admins can use tags
    $str = htmlspecialchars($str);//remove html special chars
    $str = str_replace("&amp;", "&", $str);//remove ampersands
  }
  return str_replace(",", "&#44;", $str);//remove commas
}

//check for table existance
function table_exist($table){
  $result = mysql_call("show tables like '$table'");
  if(!$result){return 0;}
  $a = mysql_fetch_row($result);
  mysql_free_result($result);
  return $a;
}

/* user image deletion */
function usrdel($no,$pwd){
  global $path,$pwdc,$onlyimgdel;
  $host = gethostbyaddr($_SERVER["REMOTE_ADDR"]);
  $delno = array();
  $delflag = FALSE;
  reset($_POST);
  while ($item = each($_POST)){
    if($item[1]=='delete'){array_push($delno,$item[0]);$delflag=TRUE;}
  }
  if($pwd==""&&$pwdc!="") $pwd=$pwdc;
  $countdel=count($delno);

  $flag = FALSE;
  for($i = 0; $i<$countdel; $i++){
    if(!$result=mysql_call("select no,ext,tim,pwd,host from ".SQLLOG." where no=".$delno[$i])){echo S_SQLFAIL;}
    else{
      while($resrow=mysql_fetch_row($result)){
        list($dno,$dext,$dtim,$dpass,$dhost)=$resrow;
        if(substr(md5($pwd),2,8) == $dpass || substr(md5($pwdc),2,8) == $dpass ||
            $dhost == $host || ADMIN_PASS==$pwd){
          $flag = TRUE;
          $delfile = $path.$dtim.$dext;	//path to delete
          if(!$onlyimgdel){
            if(!mysql_call("delete from ".SQLLOG." where no=".$dno)){echo S_SQLFAIL;} //sql is broke
          }
          if(is_file($delfile)) unlink($delfile);//Deletion
          if(is_file(THUMB_DIR.$dtim.'s.jpg')) unlink(THUMB_DIR.$dtim.'s.jpg');//Deletion
        }
      }
      mysql_free_result($result);
    }
  }
  if(!$flag) error(S_BADDELPASS);
}

/*password validation */
function valid($pass){
  if($pass && $pass != ADMIN_PASS) error(S_WRONGPASS);

  head($dat);
  echo $dat;
  echo "[<a href=\"".PHP_SELF2."\">".S_RETURNS."</a>]\n";
  echo "[<a href=\"".PHP_SELF."\">".S_LOGUPD."</a>]\n";
  echo "<table width='100%'><tr><th bgcolor=#E08000>\n";
  echo "<font color=#FFFFFF>".S_MANAMODE."</font>\n";
  echo "</th></tr></table>\n";
  echo "<p><form action=\"".PHP_SELF."\" method=POST>\n";
  // Mana login form
  if(!$pass){
    echo "<center><input type=radio name=admin value=del checked>".S_MANAREPDEL;
    echo "<input type=radio name=admin value=post>".S_MANAPOST."<p>";
    echo "<input type=hidden name=mode value=admin>\n";
    echo "<input type=password name=pass size=8>";
    echo "<input type=submit value=\"".S_MANASUB."\"></form></center>\n";
    die("</body></html>");
  }
}

/* Admin deletion */
function admindel($pass){
  global $path,$onlyimgdel;
  $delno = array(dummy);
  $delflag = FALSE;
  reset($_POST);
  while ($item = each($_POST)){
   if($item[1]=='delete'){array_push($delno,$item[0]);$delflag=TRUE;}
  }
  if($delflag){
    if(!$result=mysql_call("select * from ".SQLLOG."")){echo S_SQLFAIL;}
    $find = FALSE;
    while($row=mysql_fetch_row($result)){
      list($no,$now,$name,$email,$sub,$com,$host,$pwd,$ext,$w,$h,$tim,$time,$md5,$fsize,)=$row;
      if($onlyimgdel==on){
        if(array_search($no,$delno)){//only a picture is deleted
          $delfile = $path.$tim.$ext;	//only a picture is deleted
          if(is_file($delfile)) unlink($delfile);//delete
          if(is_file(THUMB_DIR.$tim.'s.jpg')) unlink(THUMB_DIR.$tim.'s.jpg');//delete
        }
      }else{
        if(array_search($no,$delno)){//It is empty when deleting
          $find = TRUE;
          if(!mysql_call("delete from ".SQLLOG." where no=".$no)){echo S_SQLFAIL;}
          $delfile = $path.$tim.$ext;	//Delete file
          if(is_file($delfile)) unlink($delfile);//Delete
          if(is_file(THUMB_DIR.$tim.'s.jpg')) unlink(THUMB_DIR.$tim.'s.jpg');//Delete
        }
      }
    }
    mysql_free_result($result);
    if($find){//log renewal
    }
  }
  // Deletion screen display
  echo "<input type=hidden name=mode value=admin>\n";
  echo "<input type=hidden name=admin value=del>\n";
  echo "<input type=hidden name=pass value=\"$pass\">\n";
  echo "<center><P>".S_DELLIST."\n";
  echo "<p><input type=submit value=\"".S_ITDELETES."\">";
  echo "<input type=reset value=\"".S_MDRESET."\">";
  echo "[<input type=checkbox name=onlyimgdel value=on><!--checked-->".S_MDONLYPIC."]";
  echo "<P><table border=1 cellspacing=0>\n";
  echo "<tr bgcolor=6080f6>".S_MDTABLE1;
  echo S_MDTABLE2;
  echo "</tr>\n";

  if(!$result=mysql_call("select * from ".SQLLOG." order by no desc")){echo S_SQLFAIL;}
  $j=0;
  while($row=mysql_fetch_row($result)){
    $j++;
    $img_flag = FALSE;
    list($no,$now,$name,$email,$sub,$com,$host,$pwd,$ext,$w,$h,$tim,$time,$md5,$fsize,$root,$resto)=$row;
    // Format
    $now=ereg_replace('.{2}/(.*)$','\1',$now);
    $now=ereg_replace('\(.*\)',' ',$now);
    if(strlen($name) > 10) $name = substr($name,0,9).".";
    if(strlen($sub) > 10) $sub = substr($sub,0,9).".";
    if($email) $name="<a href=\"mailto:$email\">$name</a>";
    $com = str_replace("<br />"," ",$com);
    $com = htmlspecialchars($com);
    if(strlen($com) > 20) $com = substr($com,0,18) . ".";
    // Link to the picture
    if($ext && is_file($path.$tim.$ext)){
      $img_flag = TRUE;
      $clip = "<a href=\"".IMG_DIR.$tim.$ext."\" target=_blank>".$tim.$ext."</a><br>";
      $size = $fsize;
      $all += $size;			//total calculation
      $md5= substr($md5,0,10);
    }else{
      $clip = "";
      $size = 0;
      $md5= "";
    }
    $bg = ($j % 2) ? "d6d6f6" : "f6f6f6";//BG color

    echo "<tr bgcolor=$bg><th><input type=checkbox name=\"$no\" value=delete></th>";
    echo "<th>$no</th><td><small>$now</small></td><td>$sub</td>";
    echo "<td><b>$name</b></td><td><small>$com</small></td>";
    echo "<td>$host</td><td align=center>$clip($size)</td><td>$md5</td><td>$resto</td><td>$tim</td><td>$time</td>\n";
    echo "</tr>\n";
  }
  mysql_free_result($result);

  echo "</table><p><input type=submit value=\"".S_ITDELETES."$msg\">";
  echo "<input type=reset value=\"".S_RESET."\"></form>";

  $all = (int)($all / 1024);
  echo "[ ".S_IMGSPACEUSAGE.$all."</b> KB ]";
  die("</center></body></html>");
}

/*-----------Main-------------*/
switch($mode){
  case 'regist':
    regist($name,$email,$sub,$com,'',$pwd,$upfile,$upfile_name,$resto);
    break;
  case 'admin':
    valid($pass);
    if($admin=="del") admindel($pass);
    if($admin=="post"){
      echo "</form>";
      form($post,$res,1);
      echo $post;
      die("</body></html>");
    }
    break;
  case 'usrdel':
    usrdel($no,$pwd);
  default:
    if($res){
      updatelog($res);
    }else{
      updatelog();
      echo "<META HTTP-EQUIV=\"refresh\" content=\"0;URL=".PHP_SELF2."\">";
    }
}

?>
