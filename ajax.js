/*
# Copyright (C) 2008 Ben Webb <dreamer@freedomdreams.co.uk>
# This file is part of AGPLMail.
# 
# AGPLMail is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# AGPLMail is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with AGPLMail.  If not, see <http://www.gnu.org/licenses/>.
*/

args = '';
 
function GetXmlHttpObject() {
    var xmlHttp;
    try {
        // Firefox, Opera 8.0+, Safari
        xmlHttp=new XMLHttpRequest();
    }
    catch (e) {
        // Internet Explorer
        try {
            xmlHttp=new ActiveXObject(Msxml2.XMLHTTP);
        }
        catch (e) {
            try {
                xmlHttp=new ActiveXObject(Microsoft.XMLHTTP);
            }
            catch (e) {
                alert("Sorry, you need a newser browser :S");
                return false;
            }
        }
    }
    return xmlHttp;
}
 
function ajax(params, container, increment) {
    xmlHttp = GetXmlHttpObject();
    xmlHttp.onreadystatechange=function() {
        if(xmlHttp.readyState==4) {
            if (increment)
                document.getElementById(container).innerHTML += xmlHttp.responseText;
            else
                document.getElementById(container).innerHTML = xmlHttp.responseText;
        }
    }
    post(params);
}
 
function post(params) {
    xmlHttp.open("POST","index.php?do=ajax",true);
    
    xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlHttp.setRequestHeader("Content-length", params.length);
    xmlHttp.setRequestHeader("Connection", "close");
 
    xmlHttp.send(params);
}
