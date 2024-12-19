#!/usr/bin/perl
#!/usr/bin/perl -w 

#
#  * @version $Id: HEADER 15930 2011-10-25 10:47:55Z jmd $
#  -------------------------------------------------------------------------
#  pdf - Export to PDF plugin for GLPI
#  Copyright (C) 2003-2011 by the pdf Development Team.
#
#  https://forge.indepnet.net/projects/pdf
#  -------------------------------------------------------------------------
#
#  LICENSE
#
#  This file is part of pdf.
#
#  pdf is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; either version 2 of the License, or
#  (at your option) any later version.
#
#  pdf is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with pdf. If not, see <http://www.gnu.org/licenses/>.
#  --------------------------------------------------------------------------
#

if (@ARGV!=2){
print "USAGE update_po.pl transifex_login transifex_password\n\n";

exit();
}
$user = $ARGV[0];
$password = $ARGV[1];

opendir(DIRHANDLE,'locales')||die "ERROR: can not read current directory\n"; 
foreach (readdir(DIRHANDLE)){ 
	if ($_ ne '..' && $_ ne '.'){

            if(!(-l "$dir/$_")){
                     if (index($_,".po",0)==length($_)-3) {
                        $lang=$_;
                        $lang=~s/\.po//;
                        
                        `wget --user=$user --password=$password --output-document=locales/$_ http://www.transifex.net/api/2/project/GLPI_behaviors/resource/glpipot/translation/$lang/?file=$_`;
                     }
            }

	}
}
closedir DIRHANDLE; 

#  
#  
