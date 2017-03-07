@echo off
goto :start
© Copyright 2016 Associazione di promozione sociale Redde Rationem

This file was authored by:

- Gianmarco Tuccini <gianmarcotuccini@redderationem.org>
- Paolo Landi <paololandi@redderationem.org>
- Marco Santini <marcosantini@redderationem.org>

This file is part of BilancioCivico.

BilancioCivico is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

BilancioCivico is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with BilancioCivico.  If not, see <http://www.gnu.org/licenses/>.

Supplemental term under GNU Affero General Public License version 3 section 7

You must retain the whole attribution (both the copyright line and the actual authors' list)
:start
echo Start of the build
echo ===
echo Initializing the build
if exist build\application.phar del build\application.phar
if exist build\application.phar.pubkey del build\application.phar.pubkey
echo ===
echo Installing the dependencies through composer
echo ---
call composer install --no-dev > nul
echo ===
echo Building the phar and its key
echo ---
echo Insert the full path to the certificate
set /p certificatePath=
echo Insert the full path to the key
set /p keyPath=
call php build.php %certificatePath% %keyPath%
echo ===
echo Finalizing the build
rd /Q /S library
echo ===
echo End of the build. Press a key to close the window.
pause > nul