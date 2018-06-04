<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=windows-1250">
        <meta name="generator" content="PSPad editor, www.pspad.com">
        <title></title>
        <script src="sylvester.js" type="text/javascript">
        </script>
        <script src="glUtils.js" type="text/javascript">
        </script>
        <script type="text/javascript" src="Collada.js">
        </script>
        <script type="text/javascript" src="glMatrix-0.9.5.min.js">
        </script>
        <script type="text/javascript" src="webgl-utils.js">
        </script>
				
        <!-- shadery nejsou psane v JS ! i kdyz je kod podobny. jazyk = GLSL -->
				<script id="shader-fs" type="x-shader/x-fragment">
            #ifdef GL_ES
            precision highp float;
            #endif
            varying vec2 vTextureCoord;
            uniform sampler2D uSampler;
            uniform bool uUseTextures;
            uniform vec3 uColor;
						
						//varying vec4 vColor;
            varying vec3 vLightWeighting;
						
            void main(void) {
            vec4 fragmentColor;
            	if (uUseTextures) {
            		fragmentColor = texture2D(uSampler, vec2(vTextureCoord.s, vTextureCoord.t));
            	}
            	else {
								fragmentColor = vec4(uColor,1.0);
							}
            gl_FragColor = vec4(fragmentColor.rgb * vLightWeighting, fragmentColor.a);
            //gl_FragColor = fragmentColor;
            }
        </script>
        <!-- vertext shader - zavolany pro kazdy vertex a vertex je predan shaderu 
        jako aVertexPosition (diky vertexPositionAttribute z drawScene - asociovan
        s bufferem), main - znasobi pozici vertexu MV a Proj matici a vysledek 
        je finalni pozice vertexu -->

        <script id="shader-vs" type="x-shader/x-vertex">
            attribute vec3 aVertexPosition;
						attribute vec3 aVertexNormal;
            attribute vec2 aTextureCoord;            
            //attribute vec4 aVertexColor;
						            
						uniform mat4 uMVMatrix;
            uniform mat4 uPMatrix;
            uniform mat3 uNMatrix;
						
						uniform bool uUseTextures;
            uniform bool uUseLighting;						
            uniform vec3 uAmbientColor;
            uniform vec3 uLightingDirection;
            uniform vec3 uDirectionalColor;

						            
						varying vec4 vColor;
            varying vec2 vTextureCoord;
            varying vec3 vLightWeighting;
						
            void main(void) {
            gl_Position = uPMatrix * uMVMatrix * vec4(aVertexPosition, 1.0);
            if (!uUseLighting) {
            vLightWeighting = vec3(1.0, 1.0, 1.0);
            } else {
            vec3 transformedNormal = uNMatrix * aVertexNormal;
            float directionalLightWeighting = max(dot(transformedNormal, uLightingDirection), 0.0);
            vLightWeighting = uAmbientColor + uDirectionalColor * directionalLightWeighting;
						//vLightWeighting = vec3(0.5,0.5,0.5);
            }
            if (uUseTextures) {
            	vTextureCoord = aTextureCoord;
            }
            else {
            	//vColor = aVertexColor;
            }
						
            }
        </script>
				
        <script type="text/javascript">
        
        function webGLStart(){
            //var testx = document.getElementById('mujlog');        
        }

		var gl = null;
		var drawTimer = null;

		function initGL(canvas){
			if (!gl){
	            try {
	                gl = canvas.getContext("experimental-webgl");
	                gl.viewportWidth = canvas.width;
	                gl.viewportHeight = canvas.height;
	            }
	            catch (e) {
	            }
				if (!gl) {
					alert("Could not initialise WebGL, sorry :-(");
				}
			}
        }

        function handleLoad(){
						//nulovani kuli nacteni noveho modelu
			delete c;
			delete gl;
			delete positionBuffer;
            delete normalBuffer;
            delete texcoordBuffer;
			delete colors;
            delete numVertexPointsMUL;
            delete numVertexPoints1;

			clearInterval(drawTimer);
						
            c = new Collada();
            var model = document.getElementById("selModelID").value;
            console.log("vypis selectu: " + model);
            console.log('nacitam model');
            c.load(model, parserCallback);
        }
        
        function parserCallback(c){
			//nulovani kuli zmene modelu

            var positionBuffer = [];
            var normalBuffer = [];
            var texcoordBuffer = [];
						var vertexIndexBuffer;
            //var vertexColorBuffer = [];
            var MPB = [];
            var MNB = [];
            var MTB = [];
            var MMM = [];
            var GVB = [];
            var GNB = [];
            var GTB = [];
						//var colors = [];
            var numVertexPointsMUL = [];
            var numVertexPoints1;

            console.log("vola se callback - vse nacteno");
            if (c.img) 
                console.log("img: " + JSON.stringify(c.img.src));
            
            // function renderStart() {
            var canvas = document.getElementById("canvas1");
            console.log("vstup do renderStart");

			initGL(canvas);
            initShaders();
            initBuffers();
            if (c.img) 
                initTexture();

            gl.clearColor(0.0, 0.0, 0.0, 1.0);
            gl.clearDepth(1.0);
            gl.enable(gl.DEPTH_TEST);
            gl.depthFunc(gl.LEQUAL);
            
            //eventlistener pro myš

            canvas.addEventListener('mousedown', handleMouseDown, true);
            //canvas.addEventListener('mousemove', handleMouseMove, true);
            //canvas.addEventListener('mouseup', handleMouseUp, true);
            document.addEventListener('mousemove', handleMouseMove, true);
            document.addEventListener('mouseup', handleMouseUp, true);
            
            
            /* Initialization code. */
            //může být zde nebo jako ovládání mouseButtonů v parserCallback (renderstart)
            if (window.addEventListener) 
                window.addEventListener('DOMMouseScroll', wheel, false);
            //window.onmousewheel = document.onmousewheel = wheel; //puvodni
            canvas.onmousewheel = wheel;
            
            drawTimer = setInterval(tick, 15);
            console.log("probehlo vykresleni");
            //} //měl by být ukončen !!!
            
            function getShader(gl, id){
                //najde na strance element s odpovidajicim id shaderu, vezme 
                //obsah vytvori fragment nebo vertext shader, 
                //preda webGL, ktere zkompiluje do podoby pro grafickou kartu. 
                var shaderScript = document.getElementById(id);
                if (!shaderScript) {
                    return null;
                }
                
                var str = "";
                var k = shaderScript.firstChild;
                while (k) {
                    if (k.nodeType == 3) {
                        str += k.textContent;
                    }
                    k = k.nextSibling;
                }
                
                var shader;
                if (shaderScript.type == "x-shader/x-fragment") {
                    shader = gl.createShader(gl.FRAGMENT_SHADER);
                }
                else 
                    if (shaderScript.type == "x-shader/x-vertex") {
                        shader = gl.createShader(gl.VERTEX_SHADER);
                    }
                    else {
                        return null;
                    }
                
                gl.shaderSource(shader, str);
                gl.compileShader(shader);
                
                if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
                    alert(gl.getShaderInfoLog(shader));
                    return null;
                }
                
                return shader;
            }
            
            
            var shaderProgram;
            function initShaders(){
                var fragmentShader = getShader(gl, "shader-fs");
                var vertexShader = getShader(gl, "shader-vs");
                
                shaderProgram = gl.createProgram();
                gl.attachShader(shaderProgram, vertexShader);
                gl.attachShader(shaderProgram, fragmentShader);
                gl.linkProgram(shaderProgram);
                
                if (!gl.getProgramParameter(shaderProgram, gl.LINK_STATUS)) {
                    alert("Could not initialise shaders");
                }
                
                gl.useProgram(shaderProgram);
                
                shaderProgram.vertexPositionAttribute = gl.getAttribLocation(shaderProgram, "aVertexPosition");
                gl.enableVertexAttribArray(shaderProgram.vertexPositionAttribute);								
								
                shaderProgram.vertexNormalAttribute = gl.getAttribLocation(shaderProgram, "aVertexNormal");
                gl.enableVertexAttribArray(shaderProgram.vertexNormalAttribute);
                
								/*
                shaderProgram.textureCoordAttribute = gl.getAttribLocation(shaderProgram, "aTextureCoord");
                gl.enableVertexAttribArray(shaderProgram.textureCoordAttribute);
                */
								
								/*
                shaderProgram.vertexColorAttribute = gl.getAttribLocation(shaderProgram, "aVertexColor");
                gl.enableVertexAttribArray(shaderProgram.vertexColorAttribute);
                */ 
								
                shaderProgram.useTexturesUniform = gl.getUniformLocation(shaderProgram, "uUseTextures");
                shaderProgram.useLightingUniform = gl.getUniformLocation(shaderProgram, "uUseLighting");
								
                shaderProgram.pMatrixUniform = gl.getUniformLocation(shaderProgram, "uPMatrix");
                shaderProgram.mvMatrixUniform = gl.getUniformLocation(shaderProgram, "uMVMatrix");
								shaderProgram.nMatrixUniform = gl.getUniformLocation(shaderProgram, "uNMatrix");
                shaderProgram.samplerUniform = gl.getUniformLocation(shaderProgram, "uSampler");
                
								shaderProgram.colorUniform = gl.getUniformLocation(shaderProgram, "uColor");
								
                shaderProgram.ambientColorUniform = gl.getUniformLocation(shaderProgram, "uAmbientColor");
                shaderProgram.lightingDirectionUniform = gl.getUniformLocation(shaderProgram, "uLightingDirection");
                shaderProgram.directionalColorUniform = gl.getUniformLocation(shaderProgram, "uDirectionalColor");
            }
            
            //buffery dat z collady
            
            function initBuffers(){
								/*
                 var positionBuffer = [];
                 var normalBuffer = [];
                 var texcoordBuffer = [];
								*/
                //buffers.position = gl.createBuffer();
                for (var g = 0; g < c.geomData.length; g++) {
                    //multiple buffesr
                    MPB.push(positionBuffer[g]);
                    MNB.push(normalBuffer[g]);
                    MTB.push(texcoordBuffer[g]);

				 	/*
                    console.log("init buffers for - vertexy: " + c.geomData[g].vertexes);
                    console.log("delka vertexu " + c.geomData[g].vertexes.length);
                    
                    console.log("init buffers for - normaly: " + c.geomData[g].normals);
                    console.log("delka normal " + c.geomData[g].normals.length);
										
										console.log("init buffers for - texcoordy: " + c.geomData[g].texcoords);
                    console.log("delka tcoord " + c.geomData[g].texcoords.length);
				 	*/
                    
                    positionBuffer[g] = gl.createBuffer();
                    gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer[g]);
                    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(c.geomData[g].vertexes), gl.STATIC_DRAW);
                    //gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(c.GV[g]), gl.STATIC_DRAW);
                    
                    //neni def va
                    //  gl.bindBuffer(gl.ARRAY_BUFFER, buffers.position);
                    //  gl.vertexAttribPointer(va, 3, gl.FLOAT, false, 0, 0);
                    //  gl.enableVertexAttribArray(va);
                    
                    
                    //neni def na
                    //if (na != -1) {
                    //buffers.normal = gl.createBuffer();
										
										
                    normalBuffer[g] = gl.createBuffer();
                    gl.bindBuffer(gl.ARRAY_BUFFER, normalBuffer[g]);
                    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(c.geomData[g].normals), gl.STATIC_DRAW);
                    //gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(c.GN[g]), gl.STATIC_DRAW);
                    
                    //    gl.bindBuffer(gl.ARRAY_BUFFER, buffers.normal);
                    //    gl.vertexAttribPointer(na, 3, gl.FLOAT, false, 0, 0);
                    //    gl.enableVertexAttribArray(na);
                    //  }
                    
                    ////neni def ta
                    //if (ta != -1) {
                    //buffers.texcoord = gl.createBuffer();
                    
                    if (c.img) {
                        texcoordBuffer[g] = gl.createBuffer();
                        gl.bindBuffer(gl.ARRAY_BUFFER, texcoordBuffer[g]);
                        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(c.geomData[g].texcoords), gl.STATIC_DRAW);
                        //gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(c.GT[g]), gl.STATIC_DRAW);
                    }
                    
                    
										//pridany color buffer
										/*
									if ((c.geomData[g].texcoords) && (!c.img)) {
										
                		var col1 = c.geomData[g].texcoords;
										for (i=0;i<col1.length;i++) {
											if (!(i % 3) && i) colors.push(1); //pridani alfa kazdou 4 tou hodnotu
											colors.push(col1[i]);
											//if (i==col1.length-1) colors.push(1);//pridani posledni alfa
											if (i == col1.length - 1) {
					  						colors.push(1);//pridani posledni alfa
					  						//for (j=0;j<3040;j++){ colors.push(1);} //specialne pro test sphere - pridani bile aby se vzrovnala delka na vertex.length/3*4
											}
										}
										console.log("barvy "+colors);
										console.log("barvy length"+colors.length);										
										
                		vertexColorBuffer = gl.createBuffer();
                		gl.bindBuffer(gl.ARRAY_BUFFER, vertexColorBuffer);
                		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(colors), gl.STATIC_DRAW);
                                    
								}*/
                //konec color buffer
								
								
                    numVertexPointsMUL[g] = c.geomData[g].vertexes.length / 3;
                    //numVertexPointsMUL[g] = c.GV[g].length / 3;                
                    //var numVertexPoints[g] = c.geomData[g].vertexes.length / 3;                               
                }                           
            }
            //var numVertexPoints1 = c.GV.length / 3;
            //var numVertexPoints = c.vertexes.length / 3;
            //var numVertexPoints = c.geomData[1].vertexes.length / 3;
            
            //nacteni textury  
            var neheTexture; //v tomto pripade globalni promenna - pri vice texturach nepouzivat
            function initTexture(){
                neheTexture = gl.createTexture();
                neheTexture.image = new Image(); //priradi novy atribut - pole image texture (vyhoda JS)
                neheTexture.image.onload = function(){ //prirazeni callback funkce po nacteni obrazku
                    handleLoadedTexture(neheTexture)
                }
                
                neheTexture.image.src = c.img.src;
            }
            
            //fce pro zpracovani textury  
            function handleLoadedTexture(texture){
                gl.bindTexture(gl.TEXTURE_2D, texture); //prirazeni "aktualni"(current) textury (jako buffer)
                gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, true); //vsechny obrazky prehodi vertikalne - rozdil v souradnych systemech
                gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, texture.image); //nacteni jako textury do grafiky
                gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.NEAREST); //jak "scalovat" - upraveni textury (rozmery) nahoru
                gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.NEAREST); //jak "scalovat" - upraveni textury (rozmery) dolu
                gl.bindTexture(gl.TEXTURE_2D, null); //neni nutne ale prakticke (vycisteni)
            }
            
            //obsluhy mysi
            var mouseDown = false;
            var lastMouseX = null;
            var lastMouseY = null;
            var modelRotationMatrix = Matrix.I(4);
            var posx = 0;
            var posy = 0;
            
            function handleMouseDown(event){
                mouseDown = true;
                lastMouseX = event.clientX;
                lastMouseY = event.clientY;
            }
            
            function handleMouseUp(event){
                mouseDown = false;
            }
            
            function handleMouseMove(event){
                if (!mouseDown) {
                    return;
                }
                var newX = event.clientX;
                var newY = event.clientY;
                
                var deltaX = newX - lastMouseX
                var newRotationMatrix = createRotationMatrix(deltaX / 10, [0, 1, 0]);
                
                var deltaY = newY - lastMouseY;
                newRotationMatrix = newRotationMatrix.x(createRotationMatrix(deltaY / 10, [1, 0, 0]));
                
                if (event.button != 1) {
                    modelRotationMatrix = newRotationMatrix.x(modelRotationMatrix);
                }
                
                //event.button - which = 2 button = 1
                if (event.button == 1) {
                    posx += (event.clientX - lastMouseX) * 0.5;
                    posy -= (event.clientY - lastMouseY) * 0.5;
                }
                
                lastMouseX = event.clientX;
                lastMouseY = event.clientY;               
            }
            
            var xRot = 0;
            var yRot = 0;
            var zRot = 0;
			var lastLoop = new Date;
            function drawScene(){
				var canvasWhite = document.getElementById("canvasWhite").checked;
				if (canvasWhite)
					gl.clearColor(1.0, 1.0, 1.0, 1.0);
				else
					gl.clearColor(0.0, 0.0, 0.0, 1.0);
							
                gl.viewport(0, 0, gl.viewportWidth, gl.viewportHeight);                
				gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT);
                
                perspective(45, gl.viewportWidth / gl.viewportHeight, 0.1, 2000.0);
								
                var istexture;
                if (c.img) 
                    istexture = true;
                else 
                    istexture = false;
                gl.uniform1i(shaderProgram.useTexturesUniform, istexture);
								/*
								//BARVY - presunuto do foru pro buffery - kazda geometrie svou barvu
								//pokud neni textura takto priradime barvu - dodat hodnotu z Collady
								uniBarva=c.geomData[1].difColor;
								//console.log("uniform color z collady: "+uniBarva);								
								gl.uniform3f(shaderProgram.colorUniform, uniBarva[0], uniBarva[1], uniBarva[2]);
								//gl.uniform4f(shaderProgram.colorUniform, uniBarva);
								//pokud je vice barev (jsou texcoodry ale neni img) - vyuzijeme colorBuffer
								//if ((colors) && (!c.img)) //colors je stale true !! jinak podminku
								if ((colors.length>=3) && (!c.img)) //iprovizace - podminka pro barvy v texcoordech
									useColorBuffer = true;
								else 
                   useColorBuffer = false;
								
								if (useColorBuffer)
									console.log("barvy true [15]");//+colors[15]);
								else 
                   console.log("barvy false [15]");//+colors[15]);
								gl.uniform1i(shaderProgram.useColorBufferUniform, useColorBuffer);
								*/
                var lighting = document.getElementById("lighting").checked;
                gl.uniform1i(shaderProgram.useLightingUniform, lighting);
        		if (lighting) {
            		gl.uniform3f(
                	shaderProgram.ambientColorUniform,
                	parseFloat(document.getElementById("ambientR").value),
                	parseFloat(document.getElementById("ambientG").value),
                	parseFloat(document.getElementById("ambientB").value)
            		);
 
            		var lightingDirection = [
                	parseFloat(document.getElementById("lightDirectionX").value),
                	parseFloat(document.getElementById("lightDirectionY").value),
                	parseFloat(document.getElementById("lightDirectionZ").value)
            		];
            		var adjustedLD = vec3.create();
           			  vec3.normalize(lightingDirection, adjustedLD);
            			vec3.scale(adjustedLD, -1);
            			gl.uniform3fv(shaderProgram.lightingDirectionUniform, adjustedLD);
 
            		gl.uniform3f(
                	shaderProgram.directionalColorUniform,
                	parseFloat(document.getElementById("directionalR").value),
                	parseFloat(document.getElementById("directionalG").value),
                	parseFloat(document.getElementById("directionalB").value)
            		);
        		}
								
                loadIdentity();

                mvTranslate([posx, posy, z]);
								mvTranslate([0, 0, -100]);

                multMatrix(modelRotationMatrix);								
                /*
                 //rotace modelu - animace
                 mvRotate(xRot, [1, 0, 0]);
                 mvRotate(yRot, [0, 1, 0]);
                 mvRotate(zRot, [0, 0, 1]);
                 */

                //vice bufferu
                var multPosBuffer = [];
                var multNorBuffer = [];
                var multTexBuffer = [];
                mvPushMatrix(); //ulozeni puvodni matice pred posunem podle geometrie modelu
                for (g = 0; g < c.geomData.length; g++) {
                	//BARVY
					//pokud neni textura takto priradime barvu - dodat hodnotu z Collady
					if (c.geomData[g].difColor) {
						uniBarva = c.geomData[g].difColor;
						//console.log("uniform color z collady: "+uniBarva);								
						gl.uniform3f(shaderProgram.colorUniform, uniBarva[0], uniBarva[1], uniBarva[2]);
						//gl.uniform4f(shaderProgram.colorUniform, uniBarva);
					}
					//konec BARVY
								
                    multPosBuffer = positionBuffer[g];
                    multNorBuffer = normalBuffer[g];
                    multTexBuffer = texcoordBuffer[g];
                    
                    
                    if (c.geomData[g].translSC) 
                        //console.log("draw - translate "+c.geomData[g].translSC);
                        mvTranslate(c.geomData[g].translSC); //posun podle geometrie modelu
                    
                    gl.bindBuffer(gl.ARRAY_BUFFER, positionBuffer[g]);
                    //gl.bindBuffer(gl.ARRAY_BUFFER, multPosBuffer);				
                    gl.vertexAttribPointer(shaderProgram.vertexPositionAttribute, 3, gl.FLOAT, false, 0, 0); //rekne webGL(shadery) jaky buffer pouzit pro vertex pozice
                    gl.enableVertexAttribArray(shaderProgram.vertexPositionAttribute);
                    
                    
                    gl.bindBuffer(gl.ARRAY_BUFFER, normalBuffer[g]);
                    //gl.bindBuffer(gl.ARRAY_BUFFER, multNorBuffer);
                    gl.vertexAttribPointer(shaderProgram.vertexNormalAttribute, 3, gl.FLOAT, false, 0, 0);
                    gl.enableVertexAttribArray(shaderProgram.vertexNormalAttribute);
                    
                    
                    if (c.img) {
                        gl.activeTexture(gl.TEXTURE0);
                        gl.bindTexture(gl.TEXTURE_2D, neheTexture);
                        gl.uniform1i(shaderProgram.samplerUniform, 0);
                        
                        gl.bindBuffer(gl.ARRAY_BUFFER, texcoordBuffer[g]);
                        //gl.bindBuffer(gl.ARRAY_BUFFER, multTexBuffer);
                        gl.vertexAttribPointer(shaderProgram.textureCoordAttribute, 2, gl.FLOAT, false, 0, 0);
                        gl.enableVertexAttribArray(shaderProgram.textureCoordAttribute);
                    }
										
                    setMatrixUniforms();
                    
                    //gl.drawArrays(gl.TRIANGLES, 0, numVertexPointsMUL[1]);
                    var wireframe = document.getElementById("wireframe").checked;
										
					if (wireframe)
						gl.drawArrays(gl.LINES, 0, numVertexPointsMUL[g]);
						//gl.drawArrays(gl.POINTS, 0, numVertexPointsMUL[g]);
					else
						gl.drawArrays(gl.TRIANGLES, 0, numVertexPointsMUL[g]);
						//gl.drawElements(gl.TRIANGLES, vertexIndexBuffer.numItems, gl.UNSIGNED_SHORT, 0);
										
                    
                }
                
                mvPopMatrix(); //vyvolani po posunu casti modelu v bufferu
                
								//FPS
								/*
								var thisLoop = new Date;
    						var fps = 1000 / (thisLoop - lastLoop);
								pomCas =thisLoop;
								
    						lastLoop = thisLoop;
								document.getElementById('mujlog').innerHTML = "FPS = "+fps;
								*/
            }
            
            //pomocne maticove funkce - mujViewer, lesson11
            var mvMatrix;
            var mvMatrixStack = [];
            //push MVmatrix
            function mvPushMatrix(m){
                if (m) {
                    mvMatrixStack.push(m.dup());
                    mvMatrix = m.dup();
                }
                else {
                    mvMatrixStack.push(mvMatrix.dup());
                }
            }
            
            //pop MVmatrix
            function mvPopMatrix(){
                if (mvMatrixStack.length == 0) {
                    throw "Invalid popMatrix!";
                }
                mvMatrix = mvMatrixStack.pop();
                return mvMatrix;
            }
            
            function loadIdentity(){
                mvMatrix = Matrix.I(4);
            }
            
            function multMatrix(m){
                mvMatrix = mvMatrix.x(m);
            }
            
            function mvTranslate(v){
                var m = Matrix.Translation($V([v[0], v[1], v[2]])).ensure4x4();
                multMatrix(m);
            }
            
            function createRotationMatrix(angle, v){
                var arad = angle * Math.PI / 180.0;
                return Matrix.Rotation(arad, $V([v[0], v[1], v[2]])).ensure4x4();
            }
            
            //fce pro rotaci
            function mvRotate(angle, v){
                multMatrix(createRotationMatrix(angle, v));
            }
            
            var pMatrix;
            function perspective(fovy, aspect, znear, zfar){
                pMatrix = makePerspective(fovy, aspect, znear, zfar);
            }
            
						
            function setMatrixUniforms(){
                gl.uniformMatrix4fv(shaderProgram.pMatrixUniform, false, new Float32Array(pMatrix.flatten()));
                gl.uniformMatrix4fv(shaderProgram.mvMatrixUniform, false, new Float32Array(mvMatrix.flatten()));
                
                var normalMatrix = mvMatrix.inverse();//pridana normalova matice
                normalMatrix = normalMatrix.transpose();
                gl.uniformMatrix4fv(shaderProgram.nMatrixUniform, false, new Float32Array(normalMatrix.flatten()));
            }
            
						/*
            function setMatrixUniforms(){
            
                gl.uniformMatrix4fv(shaderProgram.pMatrixUniform, false, pMatrix);
                
                gl.uniformMatrix4fv(shaderProgram.mvMatrixUniform, false, mvMatrix);
                
                
                
                var normalMatrix = mat3.create();
                
                mat4.toInverseMat3(mvMatrix, normalMatrix);
                
                mat3.transpose(normalMatrix);
                
                gl.uniformMatrix3fv(shaderProgram.nMatrixUniform, false, normalMatrix);
                
            }
						*/
						

            //zmena rotace pomoci casovace - nezavisi na rychlosti PC oproti pouhemu pricitnani
            var lastTime = 0;
            function animate(){
                var timeNow = new Date().getTime();
                if (lastTime != 0) {
                    var elapsed = timeNow - lastTime;
                    
                    xRot += (90 * elapsed) / 1000.0;
                    yRot += (90 * elapsed) / 1000.0;
                    zRot += (90 * elapsed) / 1000.0;
                }
                lastTime = timeNow;
            }
            
            function tick(){
                drawScene();
                //animate(); //pro animaci - neni potřeba      
            }
        }
        
        //fce pro ovládání mouseWheel

        var z = -100.0;
        function handle(delta){
            if (delta < 0) 
                //down
                z -= 10;
            else 
                //up
                z += 10;
        }
        
        function wheel(event){
            var delta = 0;
            if (!event) 
                event = window.event;
            if (event.wheelDelta) {
                delta = event.wheelDelta / 120;
                if (window.opera) 
                    delta = -delta;
            }
            else 
                if (event.detail) {
                    delta = -event.detail / 3;
                }
            if (delta) 
                handle(delta);
            if (event.preventDefault) 
                event.preventDefault();
            event.returnValue = false;
        }
        
        //konec mouseWheel
        
        window.onload = handleLoad;
        </script>
    </head>
    <body>
    	<center>
			<h2>WebGL Collada Viewer</h2>
			<canvas id="canvas1" style="border: 1px solid blue;" width="500" height="500">
			</canvas>
		</center>
        
        <div style="font-family: fixed-width; font-size: small;" id="log">
        </div>
        <br/>
        <b>Model:<b>
        <select name="selModel" id="selModelID" onchange="handleLoad();">
            <!-- onchange="handleLoad(); nebo parserCallback(); nebo tak nějak uzpůsobit" -->
        <?php
		$dir = "./modely/";
		$d = dir($dir);
		while ($file = $d->read()) {

			if (is_file($dir.$file) && strpos($file, ".dae") !== false) {
				echo "<option value=\"{$dir}{$file}\">{$file}</option>";
			//pridat soubor do selectu
			}
 		}
		?>
        </select>
        <br/>
		<br>
		<input type="checkbox" id="lighting" /> Use lighting 
		<input type="checkbox" id="wireframe" /> Wireframe
		<input type="checkbox" id="canvasWhite" /> White canvas 
		<br>
		<br/>
		<b>Directional light:<b>  
		<table style="border: 0; padding: 10px;"> 
        <tr> 
            <td><b>Direction:</b> 
            <td>X: <input type="text" id="lightDirectionX" value="-0.25" /> 
            <td>Y: <input type="text" id="lightDirectionY" value="-0.25" /> 
            <td>Z: <input type="text" id="lightDirectionZ" value="-1.0" /> 
        </tr> 
        <tr> 
            <td><b>Colour:</b> 
            <td>R: <input type="text" id="directionalR" value="0.8" /> 
            <td>G: <input type="text" id="directionalG" value="0.8" /> 
            <td>B: <input type="text" id="directionalB" value="0.8" /> 
        </tr> 
		</table>  
		<b>Ambient light:</b> 
		<table style="border: 0; padding: 10px;"> 
        <tr> 
            <td><b>Colour:</b> 
            <td>R: <input type="text" id="ambientR" value="0.2" /> 
            <td>G: <input type="text" id="ambientG" value="0.2" /> 
            <td>B: <input type="text" id="ambientB" value="0.2" /> 
        </tr> 
		</table> 
    </body>
</html>
