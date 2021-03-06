/****
 * Original codeprotect by Tijmen Schep, Holland, 9-10-2005
 * Updated for Tinymce 3.x by Greg Smith, UK, 19-02-2008
 * Updated to stop tinyMCE munging code by Ben Hitchcock, Australia, 30-10-2009
 ****/

(function() {
		  
	// Load plugin specific language pack
	//tinymce.PluginManager.requireLangPack('codeprotect');

	tinymce.create('tinymce.plugins.CodeprotectPlugin', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
        init : function(ed, url) {
            
            ed.onBeforeSetContent.add(function(ed, o) {
                        
            	var startPos = 0;
            	var endPos = 0;
            	
            	var startTag = "<?";
            	var endTag = "?>";
            	var running = true;
            	
            	// This section encodes all your php code, so things like: 
            	//		echo "<td nowrap>"; 
            	// don't get encoded into: 
            	//		echo "<td nowrap="nowrap">";
            	
				while((running == true) && (o.content.indexOf(startTag) != -1)){
					startPos = o.content.indexOf(startTag);
					endPos = o.content.indexOf(endTag);
					
					if(endPos != -1){
						encodedPHPCode = escape(o.content.substr(startPos + startTag.length, endPos - (startPos + startTag.length)));
					
						o.content = o.content.substr(0, startPos) + "<!--CODE" + encodedPHPCode + "CODE-->" + o.content.substr(endPos + endTag.length);
					} else {
						running = false;
					}
				}
								
				
				//firefox fix
				o.content = o.content.replace(/&amp;quot;mceNonEditable&amp;quot;/gi, "mceNonEditable");
				//url encoding fix
				o.content = o.content.replace(/'/gi, "'");
				o.content = o.content.replace(/&quot;/gi, '"');
				//End Fixes URL Encoding---
				
            });
           
            ed.onPostProcess.add(function(ed, o) {
                if (o.get) {
                
                	var startPos = 0;
	            	var endPos = 0;
	            	
	            	var startTag = "<!--CODE";
	            	var endTag = "CODE-->";

	            	var altstartTag = "&lt;!--CODE";
	            	var altendTag = "CODE--&gt;";

	            	var running = true;
            	
					while((running == true) && ((o.content.indexOf(startTag) != -1)  || (o.content.indexOf(altstartTag) != -1))){
						startPos = o.content.indexOf(startTag);
						endPos = o.content.indexOf(endTag);

						altstartPos = o.content.indexOf(altstartTag);
						altendPos = o.content.indexOf(altendTag);
						
						if(endPos != -1){
							encodedPHPCode = o.content.substr(startPos + startTag.length, endPos - (startPos + startTag.length));
							decodedPHPCode = unescape(encodedPHPCode);
						
							o.content = o.content.substr(0, startPos) + "<?" + decodedPHPCode + "?>" + o.content.substr(endPos + endTag.length);
                        }
                        else if(altendPos != -1) {
							altencodedPHPCode = o.content.substr(altstartPos + altstartTag.length, altendPos - (altstartPos + altstartTag.length));
							altdecodedPHPCode = unescape(altencodedPHPCode);

							o.content = o.content.substr(0, altstartPos) + "<?" + altdecodedPHPCode + "?>" + o.content.substr(altendPos + altendTag.length);
						} else {
							running = false;
						}
					}
					
					o.content = o.content.replace(/&lt;\?/gi, "<?");
					o.content = o.content.replace(/\?&gt;/gi, "?>");
					//firefox javascript mceNonEditable insert fix
					o.content = o.content.replace(/&amp;quot;mceNonEditable&amp;quot;/gi, "mceNonEditable");
					//url encoding fix
					o.content = o.content.replace(/'/gi, "'");
					o.content = o.content.replace(/&quot;/gi, '"');
					//End Fixes URL Encoding---				
					
                }
            });
           
        },

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : 'CodeProtect plugin',
				author : 'Ben Hitchcock (Updated from original by Tijmen Schep)',
				authorurl : 'http://www.hotpebble.com',
				infourl : '',
				version : "1.2"
			};
		}
				
	});

	// Register plugin
	tinymce.PluginManager.add('codeprotect', tinymce.plugins.CodeprotectPlugin);
	
})();

 
