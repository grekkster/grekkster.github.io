/**                      
 * @author jiri panek
 */

if (!("console" in window)) {
  window.console = { log: function(s) {
                       var l = document.getElementById('log');
                       if (l) {
                         l.innerHTML = l.innerHTML + "<span>" + s.toString() + "</span><br>";
                       }
                     }
                   };
}

function runSoon(f) {
  setTimeout(f, 0);
}


//muj log
function mujlog (vypis)
{
var log1 = document.getElementById('mujlog');
	log1.innerHTML += vypis.toString()+ "</span><br>";
}
/*
function log(msg) {
	document.getElementById("LOG").innerHTML += (msg + "\n");
}
*/


Collada = function() {
	//this.onload = function() {}
	//this.upAxis = 'Y';
	//this.images = {};
	//this.meshes = new Array();
	this.meshes = new Array();
	this.geomData = new Array();
	//this.loaded = false;
	
}


Collada.prototype.load = function(src, callback){
//fucntion load (src) {
	//this.loaded = false;
	var collada = this; // 2)
	//var self = this; // 3)
	var req = new XMLHttpRequest();
	//req.callbackFunc = this.parse; // 1)
	req.onreadystatechange = function(){
		// Status of 0 handles files coming off the local disk
		if (req.readyState == 4 && (req.status == 200 || req.status == 0)) {		  
			var xml = req.responseXML;
			collada.parse(xml); // 2)
			if (callback)
				callback(collada);
			//self.parse(xml); //3)
			//this.callbackFunc(xml); // 1)
			//if (self._loadHandler) {
      //              runSoon(function () { self._loadHandler.apply(window); });
      //          }
			//this.loaded=true;
		}
	}
	req.open("GET", src, true);
    req.overrideMimeType("text/xml");
    req.setRequestHeader("Content-Type", "text/xml");
    req.send(null);
	console.log("dokument byl nacten");
}

Collada.prototype.parse = function(xml) {
	console.log("vstup do parse");
	var doc = new XPathHelper(xml, {'c': 'http://www.collada.org/2005/11/COLLADASchema'}); //2)
	var root = xml.documentElement;
	
	//pomocne funkce ze sporefile.js
	function getNode(xpathexpr, ctxNode) {
      if (ctxNode == null)
        ctxNode = xml;
      console.log("xpath: " + xpathexpr);
      return xml.evaluate(xpathexpr, ctxNode, nsResolver, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
    }
	
	function nsResolver(prefix){
		var ns = {
			'c': 'http://www.collada.org/2005/11/COLLADASchema'
		};
		return ns[prefix] || null;
	}
    //konec pomocnych fci ze sporefile
	
	
	//UPAXIS
	var upAxisTag = root.getElementsByTagName("up_axis")[0];
    if (upAxisTag)
    {
	  //hodnota je pøímo v tagu <up_axis> -> childNode hodnota napø. Y_UP
      //xmlObject.upAxis = upAxisTag.childNodes[0].nodeValue;
	  this.upAxis = upAxisTag.childNodes[0].nodeValue; 
	  //var upAxis = root.getElementsByTagName("up_axis")[0].firstChild.nodeValue;
    }
	
	//2)
	
	var upAxisNode = doc.selectSingleNode('//c:asset/c:up_axis');
	if(upAxisNode) {
		switch(upAxisNode.textContent) {
			case 'Z_UP': this.upAxis = 'Z'; break;
			case 'Y_UP': this.upAxis = 'Y'; break;
		}
	}
	
	//3)
	// pull out the Z-up flag
    var isYUp = true;
    var upAxisNode = getNode('//c:asset/c:up_axis'); //dodefinovat getnode !!!
    //nahradit getElementsByTagName("up_axis").nodeValue; firstChild + [0]...
    if (upAxisNode) {
      var val = nodeText(upAxisNode);
      if (val.indexOf("Z_UP") != 1)
        isYUp = false;
    }
	
	console.log(this.upAxis);
	console.log(isYUp);
	
	//IMAGES
		//collada mùže mít více <library_images> !
		var textures = { };
		var imageRef = [];
		var images = xml.getElementsByTagName("image");
		console.log(images);
		var jmena = document.getElementById("jmena");
		var adresy = document.getElementById("adresy");
	
		for (var imgCount = 0; imgCount < images.length; imgCount++) {
			var img = images[imgCount];
			if (!img)
				continue;
				
			var name = img.getElementsByTagName("init_from")[0].firstChild.nodeValue;	
			var name1 = img.getAttribute("name"); //atribut name
			var imageId = img.getAttribute("id");
			var name2 = nodeText(img.getElementsByTagName("init_from")[0].firstChild);
		
			var img_src = img.getElementsByTagName("init_from")[0].firstChild.nodeValue; //jeste predelat na PNG !
			//nelze - musí být provedeno manuálnì
			//var img_src2 = getAbsolutePath(img.getElementsByTagName("init_from")[0].firstChild.nodeValue);
			//var textureName = texture.getElementsByTagName("init_from")[0].childNodes[0].nodeValue;

     		 // convert tga's to png's
      		if (name.substr(-4).toLowerCase() == ".tga")
       	 	name = name.substr(0, name.length-3) + "png";

      		var uri = xml.baseURI.toString();
      		uri = uri.substr(0, uri.lastIndexOf("/")) + "/" + name;

      		var img = new Image();
			img.src = uri;
			if (!img.src)
				alert(uri + " nelze naèíst");
					// ZDE POKUD OBRAZEK NEEXISTUJE -> VYPSAT ALERT

	  	textures[images[imgCount]] = img;
			console.log("textures");
			console.log([images[imgCount]]);
			console.log("//textures");
			
			console.log("Image reference: "+img_src+" @"+imageId+":"+name1);
	  		
			imageRef[imageId]= {
				source:img_src,
				id:imageId,
				name:name1
			};
			
			console.log("Image reference: "+uri+" @"+imageId+":"+name1);
			console.log(uri);
			console.log(" name " +name+" name1 " +name1+" name2 " +name2);
			
    }
		
	
	//MESH	
		//position
		
		//normals
		
		//texcoords
		
		var libsGeo1 = doc.selectSingleNode('//c:library_geometries');
		var libsGeo2 = getNode('//c:library_geometries');
		var libsGeo = xml.getElementsByTagName("library_geometries");
		console.log(libsGeo);
		var vertexy=[];
		for (var ilibGeo = 0; ilibGeo<libsGeo.length ; ilibGeo++){
			var geo = libsGeo[ilibGeo];
			var geometries = geo.getElementsByTagName("geometry");
			var geometries1 = [];
			
			var GV = [];
			var GN = [];
			var GT = [];
			
			console.log(geometries);

			for (var igeometry = 0; igeometry < geometries.length; igeometry++) {
	  	var geom = geometries[igeometry];
	  	console.log(geom);
	  	var geomID = geom.getAttribute("id");
	  	var mesh = geometries[igeometry].getElementsByTagName("mesh")[0];
	  	console.log("mesh" + mesh);
	  	var vertices = null;
	  	var normals = null;
	  	var texcoords = null;
	  	
	  	var sources = mesh.getElementsByTagName("source");
	  	console.log(sources);
	  	var source = {};
				
				var vertexes = []; //definovat výše jako public pro collada, aby bylo souèástí objektu
				var normals = []; //definovat výše jako public pro collada, aby bylo souèástí objektu
				var texcoords = []; //definovat výše jako public pro collada, aby bylo souèástí objektu
				var vertexes1 = [];
				var normals1 = [];
				var texcoords1 = [];
				
				var ukazatelNormal = null;
				var ukazatelVertex = null;
				var ukazatelTexcoord = null;
				
				var offsetVertex;
				var offsetNormal;
				var offsetTexcoord;
				
				for (var isource = 0; isource < sources.length; isource++) {
					aktsource = sources[isource];
					sid = aktsource.getAttribute("id");
					console.log("sid: " + sid)
					//upravit float array do vhodného formátu
					var flarray = aktsource.getElementsByTagName("float_array")[0];
					
					var fdata = flarray.textContent;
					var fdata1 = rozdelFloat(fdata);
					
					console.log("data1: " + fdata1[3]);

					fdata2 = rozdelFloat(aktsource.getElementsByTagName("float_array")[0].textContent);
					fdata3 = parseFloatArray(flarray);
					console.log("vybrane hodnoty2: " + fdata2[3]);
					console.log("vybrane hodnoty3: " + fdata3[3]);					
					console.log("delka pole fdata: " + fdata1.length);
					console.log("delka pole fdata: " + fdata2.length);
					console.log("delka pole fdata4: " + fdata3.length);
					
					var jsobj = '{ "x": "Hello, World!", "y": [1, 2, 3] }';
					jsData = JSON.parse(jsobj);
					jsData1 = JSON.stringify(fdata1);
					
				  //STRIDE
					var accessor = aktsource.getElementsByTagName("accessor")[0];
					var stride = parseInt(accessor.getAttribute("stride"));
					
					source[sid] = {
						stride: stride,
						data: fdata2
					}
					
					
				}; //KONEC SOURCES
			
				//Vertices
				var vertices = mesh.getElementsByTagName("vertices")[0];
				var vertId = vertices.getAttribute("id");
				var inpVert = vertices.getElementsByTagName("input")[0]; 
				
				if (inpVert.getAttribute("semantic") === "POSITION") {
					ukazatelVertices = inpVert.getAttribute("source").substr(1);
					console.log("ukazatelVertices ve VERTICES: " + ukazatelVertices);
				}
				
				inpVert = vertices.getElementsByTagName("input")[1]
				
				if ((inpVert) && (inpVert.getAttribute("semantic") === "NORMAL")) {
					ukazatelNormal = inpVert.getAttribute("source").substr(1);
					console.log("ukazatelNormal ve VERTICES: " + ukazatelNormal);
					//normals = source[ukazatelNormal].data;
				}
				
				if ((inpVert) && (inpVert.getAttribute("semantic") === "TEXCOORD")) { 
					ukazatelTexcoord = inpVert.getAttribute("source").substr(1);
					console.log("ukazatelTexcoord ve VERTICES: " + ukazatelTexcoord);
				}
				//Konec Vertices
				
				//TRIANGLES
				
				//+offset
				
				//dvojitý ukazatel source - nejprve na vertices a z nìj na position
				var triangles = mesh.getElementsByTagName("triangles")[0]; // [0] - vezme jen prvni pole triangles, dalsi vynecha !!4
				console.log("triangles"+triangles);
				var tris =  mesh.getElementsByTagName("triangles");				
				console.log("tris"+tris);

        var triData1 = [];
        var triData2 = [];
        var triData3 = [];
				var inpTri1;
        for (var itris = 0; itris < tris.length; itris++) {
            tri = tris[itris];
						var inpTri1 = tri.getElementsByTagName("input");	//spravny pocet input tagu v !kazdem! triangles zvlast--dole mozna spatne pocty kuli puvodnimu inpTri
            //ukladat inpTri[] do pole
						
						var triIndex1 = tri.getElementsByTagName("p")[0].textContent;
            pom = rozdelInt(triIndex1);
            if (itris == 0) {
                triData1 = pom;;
						
            }
            
            if (triData1 == triData3) 
                console.log("triData1 = triData3");
            else 
                console.log("triData1 != triData3");
            
						
						triData3=triData3.concat(pom);
						console.log("tridata3 - concat "+triData3[7127]);
						console.log("tridata1 - concat "+triData1[7127]);
						console.log("dylka 1 3 "+triData1.length +" "+triData3.length);
            
        }
				
				//KONEC VICE TRIANGLES
				
				
				if (triangles) 
				{
				var inpTri = triangles.getElementsByTagName("input");
				var triIndex = triangles.getElementsByTagName("p")[0].textContent;
				//triData DOBRE !!	
				var triData = rozdelInt(triIndex);
				console.log("triIndex: " + triData[5]);
				var triOb = {};
				var offsetVertex = 0;
				for (var i = 0; i < inpTri.length; i++) {
					
					var semantic = inpTri[i].getAttribute("semantic");
					var ukazatel = inpTri[i].getAttribute("source").substr(1)
					if (ukazatel == vertId) 
						ukazatel = ukazatelVertices;
		
					var offset = parseInt(inpTri[i].getAttribute("offset"));
					
					triOb[semantic] = {
						ukazatel: ukazatel,
						offset: offset
					}
					
					console.log('triOb.ukazatel: ' + triOb[semantic].ukazatel + 'triOb.offset: ' + triOb[semantic].offset);
					//triOb[semantic].offset = inpTri[i].getAttribute("offset");
					
					//konec pokusu
					
					if (inpTri[i].getAttribute("semantic") == "VERTEX") {
						ukazatelVertex = inpTri[i].getAttribute("source").substr(1);
						offsetVertex = parseInt(inpTri[i].getAttribute("offset"));
						//dvojity odkaz
						if (ukazatelVertex == vertId) 
							ukazatelVertex = ukazatelVertices;

						console.log("ukazatelVertex: " + ukazatelVertex + " offset: " + offsetVertex);					
				
					}
					
					else 
						if (inpTri[i].getAttribute("semantic") == "NORMAL") {
							ukazatelNormal = inpTri[i].getAttribute("source").substr(1);
							offsetNormal = parseInt(inpTri[i].getAttribute("offset"));
							console.log("ukazatelNormal: " + ukazatelNormal + " offset: " + offsetNormal);
										
						}
						
						else 
							if (inpTri[i].getAttribute("semantic") == "TEXCOORD") {
								ukazatelTexcoord = inpTri[i].getAttribute("source").substr(1);
								offsetTexcoord = parseInt(inpTri[i].getAttribute("offset"));
								console.log("ukazatelTexcoord: " + ukazatelTexcoord + " offset: " + offsetTexcoord);
								
                                
							}
				};
				
				//priradit hodnoty z polí indexù - stride*index+offset
				//VERTEXES (position)
				
				
				maxOffset = 0;
				if (offsetVertex>maxOffset) maxOffset=offsetVertex;
				if (offsetNormal>maxOffset) maxOffset=offsetNormal;
				if (offsetTexcoord>maxOffset) maxOffset=offsetTexcoord;
				maxOffset +=1;

				for (var i = 0; i < triData3.length/maxOffset; i++) { //opraveno aby nedelalo body navic
					for (var j = 0; j < (source[ukazatelVertex].stride); j++) //source[ukazatelVertex].stride.length
					{
						//spore
						//vertexes.push(source[ukazatelVertex].data[triData3[i * source[ukazatelVertex].stride+ offsetVertex]*source[ukazatelVertex].stride + j ]);
						
						vertexes.push(source[ukazatelVertex].data[triData3[i * inpTri1.length + offsetVertex]*source[ukazatelVertex].stride + j ]); //SPRAVNE x misto ipTri1 - doladit, asi maxOffset !!!!
						//vertexes.push(source[ukazatelVertex].data[triData3[i * maxOffset + offsetVertex]*source[ukazatelVertex].stride + j ]); 
						
						//vertexes1[i+j] = (source[ukazatelVertex].data[(triData3[i] * source[ukazatelVertex].stride) + j + offsetVertex]);
					}
				}
				console.log(vertexes[15]); //kontrola
				
				
				
				//console.log(source[ukazatelVertex].data[9]); //kontrola
				//HODNOTY SE LISI !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! chyba je v offsetu
				
				//NORMALS
				
				if (ukazatelNormal) {
					if (!offsetNormal) offsetNormal = offsetVertex; //nebo default tedy 0
			//for (var i = 0; i < (triData3.length); i++) {
				for (var i = 0; i < triData3.length/maxOffset; i++) { //opraveno aby nedelalo body navic
				for (var j = 0; j < (source[ukazatelNormal].stride); j++) //source[ukazatelVertex].stride.length
				{
					//spore
					//normals.push(source[ukazatelNormal].data[triData3[i * source[ukazatelNormal].stride+ offsetNormal]*source[ukazatelNormal].stride  + j ]);
					
					normals.push(source[ukazatelNormal].data[triData3[i * inpTri1.length + offsetNormal] * source[ukazatelNormal].stride + j]); //SPRAVNE x misto ipTri1 - doladit, asi maxOffset !!!!
					//normals.push(source[ukazatelNormal].data[triData3[i *maxOffset+ offsetNormal]*source[ukazatelNormal].stride  + j ]);
					
					//normals1[i + j] = (source[ukazatelNormal].data[(triData3[i] * source[ukazatelNormal].stride) + j + offsetNormal]);
					}
				}
				console.log(normals[3]); //kontrola
			}
				
				
				//TEXCOORDS
				
				if (ukazatelTexcoord) {
					if (!offsetTexcoord) offsetTexcoord = offsetVertex; //nebo default tedy 0
			//for (var i = 0; i < triData3.length; i++) {
				for (var i = 0; i < triData3.length/maxOffset; i++) { //opraveno aby nedelalo body navic
				for (var j = 0; j < (source[ukazatelTexcoord].stride); j++) //source[ukazatelVertex].stride.length
				{
					//spore
					//texcoords.push(source[ukazatelTexcoord].data[triData3[i * source[ukazatelTexcoord].stride+ offsetTexcoord]*source[ukazatelTexcoord].stride  + j ]);
					texcoords.push(source[ukazatelTexcoord].data[triData3[i * inpTri1.length + offsetTexcoord] * source[ukazatelTexcoord].stride + j]); //SPRAVNE x misto ipTri1 - doladit, asi maxOffset !!!!
					//texcoords.push(source[ukazatelTexcoord].data[triData3[i * maxOffset + offsetTexcoord] * source[ukazatelTexcoord].stride + j]);
					
					texcoords1[i + j] = (source[ukazatelTexcoord].data[(triData3[i] * source[ukazatelTexcoord].stride) + j + offsetTexcoord]);
				}
			}
				console.log(texcoords[9]); //kontrola
				console.log(texcoords.length);
				
		}
				
				
				
				//KONEC DRUHE
				
			} //konec if triangles
			
			//prirazeni barvy ke geometrii(pozdeji opravit pro kazdy triangle atd, pridat dalsi parametry, take texturu!)
		var libsVScenes = xml.getElementsByTagName("library_visual_scenes");
        //pozdeji pridat ostatni paramatry, ne jen translate (material, rotace..)
				for (var iVS = 0; iVS < libsVScenes.length; iVS++) {
				var libVS = libsVScenes[iVS];
				console.log("hledani barvy pro geometri - "+geomID);
				//var igVS = libVS.getElementsByTagName("instance_geometry")[0]; //JEN PRVNI - POZDEJI VYLEPSIT
				var igVS = libVS.getElementsByTagName("instance_geometry");
				for (var iIG=0;iIG<igVS.length;iIG++) {
				var igUrl = igVS[iIG].getAttribute("url").substr(1);
				if (igUrl == geomID) {
					if (igVS[iIG].getElementsByTagName("instance_material")[0]) {
						iIM = igVS[iIG].getElementsByTagName("instance_material")[0];
						iMatTarget = igVS[iIG].getElementsByTagName("instance_material")[0].getAttribute("target").substr(1); //JEN PRVNI - POZDEJI VYLEPSIT, lunar_vehicle - eror
						//pridat testovani <bind_vertex_input -> textura
						var libMat = xml.getElementsByTagName("library_materials")[0];
						var materials = libMat.getElementsByTagName("material");
						//pro kazdy zjistit ID = iMatTarget
						for (var iMat = 0; iMat < materials.length; iMat++) {
							var material = materials[iMat];
							materialID = material.getAttribute("id");
							if (iMatTarget == materialID) {
								console.log("materialID " + materialID);
								//iMatEffect = material.firstChild.getAttribute("url"); //instance effect url ukazuje na url efektu
								iMatEffect = material.getElementsByTagName("instance_effect")[0].getAttribute("url").substr(1);
								console.log("iMatEffect " + iMatEffect);
								var libEffect = xml.getElementsByTagName("library_effects")[0];
								effects = libEffect.getElementsByTagName("effect");
								for (iEf = 0; iEf < effects.length; iEf++) {
									effect = effects[iEf];
									effectID = effect.getAttribute("id");
									if (iMatEffect == effectID) {
										if (effect.getElementsByTagName("diffuse")[0]) {
											efDiffuse = effect.getElementsByTagName("diffuse")[0];
											//if (!efDiffuse.getElementsByTagName("texture")) { //muze byt z textury - nema barvu 
											if (efDiffuse.getElementsByTagName("color")[0]) { //muze byt z textury - nema barvu
												difColor = rozdelFloat(efDiffuse.getElementsByTagName("color")[0].textContent);
												geometries[geomID].difColor = difColor;
												console.log("diffuse barva " + geometries[geomID].difColor);
											}
										}
									}
								}
							}
					
						}
					}
				}
				}
			}
			
			//VICE GEOMETRIES
			
			
			geometries[geomID].vertexes = vertexes;
			geometries[geomID].normals = normals;
			geometries[geomID].texcoords = texcoords;
			
			
			
			GV.push(odstranPrazdne(geometries[geomID].vertexes));
			GN.push(odstranPrazdne(geometries[geomID].normals));
			GT.push(odstranPrazdne(geometries[geomID].texcoords));
			
			
			this.geomData.push(geometries[geomID]);
			
			console.log("igeometry "+igeometry);
			console.log("geom length "+this.geomData.length);
			console.log("geom"+this.geomData);
			
			};
		};
		
		
		//POCATECNI SOURADNICE GEOMETRIE
        var libsVScenes = xml.getElementsByTagName("library_visual_scenes");
        //dodelat pokud muze byt vice ?
        //pozdeji pridat ostatni paramatry, ne jen translate (material, rotace..)
        for (var iVS = 0; iVS < libsVScenes.length; iVS++) {
            var libVS = libsVScenes[iVS];
            console.log("libVS " + libVS);
            var nodesVS = libVS.getElementsByTagName("node");
            console.log("childNodes" + nodesVS);
            //pro kazdy node id instance geometry + pridat ke geom translate pokud existuje
            for (var iNode = 0; iNode < nodesVS.length; iNode++) {
                var nodeVS = nodesVS[iNode];
										//nulovani pred cyklem
										trData = 0; 
										ukazIG = 0;
										diteVS = nodeVS.firstChild;
                    while (diteVS) {
                    	if (diteVS.tagName == "translate") {
												//jak najde translate projet znova sourozence a najit instance geom tu ulozit
											trData=diteVS.textContent;								
                      }
											if (diteVS.tagName == "instance_geometry")
												ukazIG = diteVS.getAttribute("url").substr(1);
											
											if (ukazIG && trData) {
												geometries[ukazIG].translSC = rozdelFloat(trData); // jeste upravit jako vektor ?
					  					}
											diteVS = diteVS.nextSibling;   											                                         
                    }
										
                }//konec iNode
        }//konec iVS
		
	//predat objekt image a mesh
	this.mesh = mesh;
  this.textures = textures;
	this.img = img;
	if (img)
		console.log("img: "+JSON.stringify(img.src));
	
	this.GV = GV;
	//console.log("GV "+GV);
	this.GN = GN;
	this.GT = GT;
	
	//this.geomData = geomData; // VYUZIVA VIEWER
	
	this.vertexes1 = vertexes1;
	this.texcoords1 = texcoords1;
	this.normals1 = normals1;
	
	//pokus
	this.vertexy = vertexy;
	
	//vsechno je hotovo, zavolame callback
  
	 /*
  	if (typeof parserCallback == "function") {
    	parserCallback(this); //predame referenci na objekt collady.
    	console.log("volani callbacku parseCallback");
  	}
	 */
  	
  	console.log("vystup z Collada.parse");
	
		return true;
	
}

//pomocne funkce z collada.js
/*
 * A simple xpath helper object to make certain xml calls more readable
 */

XPathHelper = function(xml, namespaceTable) {
	this.xml = xml;
	if(namespaceTable == null) { namespaceTable = {}; }
	this.namespaceResolver = function(prefix) {
		return namespaceTable[prefix] || null;
	}
}

XPathHelper.prototype.selectNodes = function(xpath, context) {
	if(context == null) { context = this.xml; }
	return this.xml.evaluate(xpath, context, this.namespaceResolver, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);
}

XPathHelper.prototype.selectSingleNode = function(xpath, context) {
	if(context == null) { context = this.xml; }
	var result = this.xml.evaluate(xpath, context, this.namespaceResolver, XPathResult.FIRST_ORDERED_NODE_TYPE, null);
	if(result != null) { return result.singleNodeValue; }
	return null;
}

XPathHelper.prototype.selectId = function(id) {
	var result = this.xml.evaluate('//*[@id="' + id + '"]', this.xml, this.namespaceResolver, XPathResult.FIRST_ORDERED_NODE_TYPE, null);
	if(result != null) { return result.singleNodeValue; }
	return null;
}

//pomocne funkce ze sporefile.js
 function getNode(xpathexpr, ctxNode) {
      if (ctxNode == null)
        ctxNode = xml;
      console.log("xpath: " + xpathexpr);
      return xml.evaluate(xpathexpr, ctxNode, nsResolver, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
    }
	
//pomocne funkce ze sporefile.js
function nodeText(n) {
  var s = "";
  for (c = n.firstChild;
       c;
       c = c.nextSibling)
  {
    if (c.nodeType != 3)
      break;
    s += c.textContent;
  }

  return s;
}

//pomocne fce c3dl

 function parseFloatArray(node) {
        var result = [];
        var prev = "";
        var child = node.firstChild;
        var currArray;
        while (child) {
            currArray = (prev + child.nodeValue).replace(/\s+/g, " ").replace(/^\s+/g, "").split(" ");
            child = child.nextSibling;
            if (currArray[0] == "") {
                currArray.unshift();
            }
            if (child) {
                prev = currArray.pop();
            }
            for (var i = 0; i < currArray.length; i++) {
                result.push(parseFloat(currArray[i]));
            }
        }
        return result;
    };

//neroydeluje podle celych cislic -> replace mezeru carkou U FLOATU JDE
function rozdelFloat(poleStr) {
	var pole = poleStr.split(" "); //split(delim ? delim : ",");
	for (var i = 0; i < pole.length; i++) {
		pole[i]=parseFloat(pole[i]);
	}
	return pole;
}

function rozdelInt(poleStr) {
	var pole = poleStr.split(" "); //split(delim ? delim : ",");
	for (var i = 0; i < pole.length; i++) {
		pole[i]=parseInt(pole[i]);
	}
	return pole;
}

function odstranPrazdne(pole) {
	//SPATNE ODSTRANUJE I NULY, NE JEN NEDEFINOVANE
	var novePole =[];
	for (var i = 0; i < pole.length; i++) {
		if (pole[i])
			novePole.push(pole[i]);		
	}
	return novePole;
}
