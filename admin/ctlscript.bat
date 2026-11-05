@echo off
rem START or STOP Services
rem ----------------------------------
rem Check if argument is STOP or START

if not ""%1"" == ""START"" goto stop

if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\hypersonic\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\server\hsql-sample-database\scripts\ctl.bat START)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\ingres\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\ingres\scripts\ctl.bat START)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\mysql\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\mysql\scripts\ctl.bat START)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\postgresql\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\postgresql\scripts\ctl.bat START)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\apache\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\apache\scripts\ctl.bat START)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\openoffice\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\openoffice\scripts\ctl.bat START)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\apache-tomcat\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\apache-tomcat\scripts\ctl.bat START)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\resin\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\resin\scripts\ctl.bat START)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\jetty\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\jetty\scripts\ctl.bat START)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\subversion\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\subversion\scripts\ctl.bat START)
rem RUBY_APPLICATION_START
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\lucene\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\lucene\scripts\ctl.bat START)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\third_application\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\third_application\scripts\ctl.bat START)
goto end

:stop
echo "Stopping services ..."
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\third_application\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\third_application\scripts\ctl.bat STOP)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\lucene\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\lucene\scripts\ctl.bat STOP)
rem RUBY_APPLICATION_STOP
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\subversion\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\subversion\scripts\ctl.bat STOP)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\jetty\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\jetty\scripts\ctl.bat STOP)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\hypersonic\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\server\hsql-sample-database\scripts\ctl.bat STOP)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\resin\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\resin\scripts\ctl.bat STOP)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\apache-tomcat\scripts\ctl.bat (start /MIN /B /WAIT C:\Users\my791\OneDrive\Desktop\wordnest\admin\apache-tomcat\scripts\ctl.bat STOP)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\openoffice\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\openoffice\scripts\ctl.bat STOP)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\apache\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\apache\scripts\ctl.bat STOP)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\ingres\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\ingres\scripts\ctl.bat STOP)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\mysql\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\mysql\scripts\ctl.bat STOP)
if exist C:\Users\my791\OneDrive\Desktop\wordnest\admin\postgresql\scripts\ctl.bat (start /MIN /B C:\Users\my791\OneDrive\Desktop\wordnest\admin\postgresql\scripts\ctl.bat STOP)

:end

