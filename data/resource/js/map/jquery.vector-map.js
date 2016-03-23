/*!
 * jVectorMap version 0.1
 *
 * Copyright 2011, Kirill Lebedev
 * Licensed under the MIT license.
 *
 */
(function( $ ){
    
    var apiParams = {
        colors: 1,
		values: 1,
		backgroundColor: 1,
		scaleColors: 1,
		normalizeFunction: 1
    };
	var apiEvents = {
		onLabelShow: 'labelShow',
		onRegionOver: 'regionMouseOver',
		onRegionOut: 'regionMouseOut',
		onRegionClick: 'regionClick'
	};
	
    $.fn.vectorMap = function(options) {
        var defaultParams = {
			map: 'world_en',
			backgroundColor: '#FFFFFF',//背景色
            color: '#ffffff',
            hoverColor: '#f63a3a',//鼠标放上去颜色
            scaleColors: ['#b6d6ff', '#005ace'],
			normalizeFunction: 'linear'
		}, map;
        
        if (options === 'addMap') {
            WorldMap.maps[arguments[1]] = arguments[2];
        } else if (options === 'set' && apiParams[arguments[1]]) {
			this.data('mapObject')['set'+arguments[1].charAt(0).toUpperCase()+arguments[1].substr(1)].apply(this.data('mapObject'), Array.prototype.slice.call(arguments, 2));
        } else {
            $.extend(defaultParams, options);
            defaultParams.container = this;
			this.css({
				position: 'relative',
				overflow: 'hidden'
			});
			map = new WorldMap(defaultParams);
            this.data('mapObject', map);
			for (var e in apiEvents) {
				if (defaultParams[e]) {
					this.bind(apiEvents[e]+'.jvectormap', defaultParams[e]);
				}
			}
        }
    };
    
    var VectorCanvas = function(width, height) {
		this.mode = window.SVGAngle ? 'svg' : 'vml';
		if (this.mode == 'svg') {
			this.createSvgNode = function(nodeName) {
				return document.createElementNS(this.svgns, nodeName);
			}
		} else {
			try {
				if (!document.namespaces.rvml) {
					document.namespaces.add("rvml","urn:schemas-microsoft-com:vml");
				}
				this.createVmlNode = function (tagName) {
					return document.createElement('<rvml:' + tagName + ' class="rvml">');
				};
			} catch (e) {
				this.createVmlNode = function (tagName) {
					return document.createElement('<' + tagName + ' xmlns="urn:schemas-microsoft.com:vml" class="rvml">');
				};
			}
			document.createStyleSheet().addRule(".rvml", "behavior:url(#default#VML)");
		}
		if (this.mode == 'svg') {
			this.canvas = this.createSvgNode('svg');
		} else {
			this.canvas = this.createVmlNode('group');
			this.canvas.style.position = 'absolute';
		}
		this.setSize(width, height);
    }
	
	VectorCanvas.prototype = {
        svgns: "http://www.w3.org/2000/svg",
        mode: 'svg',
        width: 0,
        height: 0,
        canvas: null,
        
        setSize: function(width, height) {
            if (this.mode == 'svg') {
                this.canvas.setAttribute('width', width);
                this.canvas.setAttribute('height', height);
            } else {
                this.canvas.style.width = width + "px";
                this.canvas.style.height = height + "px";
                this.canvas.coordsize = width+' '+height;
                this.canvas.coordorigin = "0 0";
                if (this.rootGroup) {
                    var pathes = this.rootGroup.getElementsByTagName('shape');
                    for(var i=0, l=pathes.length; i<l; i++) {
                        pathes[i].coordsize = width+' '+height;
                        pathes[i].style.width = width+'px';
                        pathes[i].style.height = height+'px';
                    }
                    this.rootGroup.coordsize = width+' '+height;
                    this.rootGroup.style.width = width+'px';
                    this.rootGroup.style.height = height+'px';
                }
            }
            this.width = width;
            this.height = height;    
        },
        
        createPath: function(config) {
            var node;
            if (this.mode == 'svg') {
                node = this.createSvgNode('path');
                node.setAttribute('d', config.path);
                node.setFill = function(color) {
                    this.setAttribute("fill", color);
                };
				node.getFill = function(color) {
                    return this.getAttribute("fill");
                };
                node.setOpacity = function(opacity) {
                    this.setAttribute('fill-opacity', opacity);
                };
            } else {
                node = this.createVmlNode('shape');
                node.coordorigin = "0 0";
                node.coordsize = this.width + ' ' + this.height;
                node.style.width = this.width+'px';
                node.style.height = this.height+'px';
                node.fillcolor = WorldMap.defaultFillColor;
                node.stroked = false;
                node.path = VectorCanvas.pathSvgToVml(config.path);
                var scale = this.createVmlNode('skew');
                scale.on = true;
                scale.matrix = '0.01,0,0,0.01,0,0';
                scale.offset = '0,0';
                node.appendChild(scale);
                var fill = this.createVmlNode('fill');
                node.appendChild(fill);
                node.setFill = function(color) {
                    this.getElementsByTagName('fill')[0].color = color;
                };
				node.getFill = function(color) {
                    return this.getElementsByTagName('fill')[0].color;
                };
                node.setOpacity = function(opacity) {
                    this.getElementsByTagName('fill')[0].opacity = parseInt(opacity*100)+'%';
                };
            }
            return node;
        },
        
        createGroup: function(isRoot) {
            var node;
            if (this.mode == 'svg') {
                node = this.createSvgNode('g');
            } else {
                node = this.createVmlNode('group');
                node.style.width = this.width+'px';
                node.style.height = this.height+'px';
                node.style.left = '0px';
                node.style.top = '0px';
                node.coordorigin = "0 0";
                node.coordsize = this.width + ' ' + this.height;
            }
            if (isRoot) {
                this.rootGroup = node;
            }
            return node;
        },
        
        applyTransformParams: function(scale, transX, transY) {
            if (this.mode == 'svg') {
                this.rootGroup.setAttribute('transform', 'scale('+scale+') translate('+transX+', '+transY+')');
            } else {
                this.rootGroup.coordorigin = (this.width-transX)+','+(this.height-transY);
                this.rootGroup.coordsize = this.width/scale+','+this.height/scale;
            }
        }
    }
	
	VectorCanvas.pathSvgToVml = function(path) {
		var result = '';
		var cx = 0, cy = 0, ctrlx, ctrly;
		return path.replace(/([MmLlHhVvCcSs])((?:-?(?:\d+)?(?:\.\d+)?,?\s?)+)/g, function(segment, letter, coords, index){
			coords = coords.replace(/(\d)-/g, '$1,-').replace(/\s+/g, ',').split(',');
			if (!coords[0]) coords.shift();
			for (var i=0,l=coords.length; i<l; i++) {
				coords[i] = Math.round(100*coords[i]);
			}
			switch (letter) {
				case 'm':
					cx += coords[0];
					cy += coords[1];
					return 't'+coords.join(',');
				break;
				case 'M':
					cx = coords[0];
					cy = coords[1];
					return 'm'+coords.join(',');
				break;
				case 'l':
					cx += coords[0];
					cy += coords[1];
					return 'r'+coords.join(',');
				break;
				case 'L':
					cx = coords[0];
					cy = coords[1];
					return 'l'+coords.join(',');
				break;
				case 'h':
					cx += coords[0];
					return 'r'+coords[0]+',0';
				break;
				case 'H':
					cx = coords[0];
					return 'l'+cx+','+cy;
				break;
				case 'v':
					cy += coords[0];
					return 'r0,'+coords[0];
				break;
				case 'V':
					cy = coords[0];
					return 'l'+cx+','+cy;
				break;
				case 'c':
					ctrlx = cx + coords[coords.length-4];
					ctrly = cy + coords[coords.length-3];
					cx += coords[coords.length-2];
					cy += coords[coords.length-1];
					return 'v'+coords.join(',');
				break;
				case 'C':
					ctrlx = coords[coords.length-4];
					ctrly = coords[coords.length-3];
					cx = coords[coords.length-2];
					cy = coords[coords.length-1];
					return 'c'+coords.join(',');
				break;
				case 's':
					coords.unshift(cy-ctrly);
					coords.unshift(cx-ctrlx);
					ctrlx = cx + coords[coords.length-4];
					ctrly = cy + coords[coords.length-3];
					cx += coords[coords.length-2];
					cy += coords[coords.length-1];
					return 'v'+coords.join(',');
				break;
				case 'S':
					coords.unshift(cy+cy-ctrly);
					coords.unshift(cx+cx-ctrlx);
					ctrlx = coords[coords.length-4];
					ctrly = coords[coords.length-3];
					cx = coords[coords.length-2];
					cy = coords[coords.length-1];
					return 'c'+coords.join(',');
				break;
			}
			return '';
		}).replace(/z/g, '');
	}
    
    var WorldMap = function(params) {
		params = params || {};
		var map = this;
		var mapData = WorldMap.maps[params.map];
		
		this.container = params.container;
		
		this.defaultWidth = mapData.width;
		this.defaultHeight = mapData.height;
		
		this.color = params.color;
		this.hoverColor = params.hoverColor;
		this.setBackgroundColor(params.backgroundColor);
		
		this.width = params.container.width();
		this.height = params.container.height();
		
		this.resize();

		$(window).resize(function(){
			map.width = params.container.width();
			map.height = params.container.height();
			map.resize();
			map.canvas.setSize(map.width, map.height);
			map.applyTransform();
		});
		
		this.canvas = new VectorCanvas(this.width, this.height);
		params.container.append(this.canvas.canvas);
		
		this.makeDraggable();
		
		this.rootGroup = this.canvas.createGroup(true);
		
		this.index = WorldMap.mapIndex;
		this.label = $('<div/>').addClass('jvectormap-label').appendTo($('body'));
		//this.labelText = $('<div/>').addClass('jvectormap-label').appendTo($('body'));
		//$('<div/>').addClass('jvectormap-zoomin').text('+').appendTo(params.container);
		//$('<div/>').addClass('jvectormap-zoomout').html('&#x2212;').appendTo(params.container);
	
		for(var key in mapData.pathes) {
			var path = this.canvas.createPath({path: mapData.pathes[key].path});
			path.setFill(this.color);
			path.id = 'jvectormap'+map.index+'_'+key;
			map.countries[key] = path;
			$(this.rootGroup).append(path);
		}
		
		$(params.container).delegate(this.canvas.mode == 'svg' ? 'path' : 'shape', 'mouseover mouseout', function(e){
			var path = e.target,
				code = e.target.id.split('_').pop(),
				labelShowEvent = $.Event('labelShow.jvectormap'),
				regionMouseOverEvent = $.Event('regionMouseOver.jvectormap');;
			
			if (e.type == 'mouseover') {
				$(params.container).trigger(regionMouseOverEvent, [code]);
				if (!regionMouseOverEvent.isDefaultPrevented()) {
					if (params.hoverOpacity) {
						path.setOpacity(params.hoverOpacity);
					}
					if (params.hoverColor) {
						path.currentFillColor = path.getFill()+'';
						path.setFill(params.hoverColor);
					}
				}
				
				map.label.html(mapData.pathes[code].name);
				//map.labelText.html(mapData.pathes[code].name);
				//map.labelText.css({color:'0xa1a1a1'});
				$(params.container).trigger(labelShowEvent, [map.label, code]);
				//$(params.container).trigger(labelShowEvent, [map.labelText, code]);
				//map.labelText.show();

				if (!labelShowEvent.isDefaultPrevented()) {
					map.label.show();	
					
					map.labelWidth = map.label.width();
					map.labelHeight = map.label.height();
				}
			} else {
				path.setOpacity(1);
				if (path.currentFillColor) {
					path.setFill(path.currentFillColor);
				}
				map.label.hide();
				$(params.container).trigger('regionMouseOut.jvectormap', [code]);
			}
		});
		
		$(params.container).delegate(this.canvas.mode == 'svg' ? 'path' : 'shape', 'click', function(e){
			var path = e.target;
			var code = e.target.id.split('_').pop();
			$(params.container).trigger('regionClick.jvectormap', [code]);
		});

		params.container.mousemove(function(e){
			if (map.label.is(':visible')) {
				map.label.css({
					color:'0xfff000',
					left: e.pageX-15-map.labelWidth,
					top: e.pageY-15-map.labelHeight
				})
			}
		});
		
		this.setColors(params.colors);
		
		this.canvas.canvas.appendChild(this.rootGroup);
		
		this.applyTransform();
		
		this.colorScale = new ColorScale(params.scaleColors, params.normalizeFunction, params.valueMin, params.valueMax);
		if (params.values) {
			this.values = params.values;
			this.setValues(params.values);
		}
		
		this.bindZoomButtons();
		
		WorldMap.mapIndex++;
	}
	
	WorldMap.prototype = {
        transX: 0,
        transY: 0,
        scale: 1,
        baseTransX: 0,
        baseTransY: 0,
        baseScale: 1,
        
        width: 0,
        height: 0,
        countries: {},
        countriesColors: {},
        countriesData: {},
        zoomStep: 1.4,
        zoomMaxStep: 4,
        zoomCurStep: 1,
        
        setColors: function(key, color) {
            if (typeof key == 'string') {
                this.countries[key].setFill(color);
            } else {
                var colors = key;
                for (var code in colors) {
					if (this.countries[code]) {
						this.countries[code].setFill(colors[code]);	
					}
                }
            }
        },
		
		setValues: function(values) {
			var max = 0,
				min = Number.MAX_VALUE,
				val;
				
			for (var cc in values) {
				val = parseFloat(values[cc]);
				if (val > max) max = values[cc];
				if (val && val < min) min = val;
			}
			this.colorScale.setMin(min);
			this.colorScale.setMax(max);
			
			var colors = {};
			for (cc in values) {
				val = parseFloat(values[cc]);
				if (val) {
					colors[cc] = this.colorScale.getColor(val);
				} else {
					colors[cc] = this.color;
				}
			}
			this.setColors(colors);
			this.values = values;
		},
		
		setBackgroundColor: function(backgroundColor) {
			this.container.css('background-color', backgroundColor);
		},
		
		setScaleColors: function(colors) {
			this.colorScale.setColors(colors);
			if (this.values) {
				this.setValues(this.values);	
			}
		},
		
		setNormalizeFunction: function(f) {
			this.colorScale.setNormalizeFunction(f);
			if (this.values) {
				this.setValues(this.values);	
			}
		},
        
        resize: function() {
            var curBaseScale = this.baseScale;
            if (this.width / this.height > this.defaultWidth / this.defaultHeight) {
                this.baseScale = this.height / this.defaultHeight;
                this.baseTransX = Math.abs(this.width - this.defaultWidth * this.baseScale) / (2 * this.baseScale);
            } else {
                this.baseScale = this.width / this.defaultWidth;
                this.baseTransY = Math.abs(this.height - this.defaultHeight * this.baseScale) / (2 * this.baseScale);
            }
            this.scale *= this.baseScale / curBaseScale;
            this.transX *= this.baseScale / curBaseScale;
            this.transY *= this.baseScale / curBaseScale;
        },
        
        reset: function() {
            this.countryTitle.reset();
            for(var key in this.countries) {
                this.countries[key].setFill(WorldMap.defaultColor);
            }
            this.scale = this.baseScale;
            this.transX = this.baseTransX;
            this.transY = this.baseTransY;
            this.applyTransform();
        },
        
        applyTransform: function() {
            var maxTransX, maxTransY, minTransX, maxTransY;
            if (this.defaultWidth * this.scale <= this.width) {
                maxTransX = (this.width - this.defaultWidth * this.scale) / (2 * this.scale);
                minTransX = (this.width - this.defaultWidth * this.scale) / (2 * this.scale);
            } else {
                maxTransX = 0;
                minTransX = (this.width - this.defaultWidth * this.scale) / this.scale;
            }
            
            if (this.defaultHeight * this.scale <= this.height) {
                maxTransY = (this.height - this.defaultHeight * this.scale) / (2 * this.scale);
                minTransY = (this.height - this.defaultHeight * this.scale) / (2 * this.scale);
            } else {
                maxTransY = 0;
                minTransY = (this.height - this.defaultHeight * this.scale) / this.scale;
            }
            
            if (this.transY > maxTransY) {
                this.transY = maxTransY;
            } else if (this.transY < minTransY) {
                this.transY = minTransY;
            }
            if (this.transX > maxTransX) {
                this.transX = maxTransX;
            } else if (this.transX < minTransX) {
                this.transX = minTransX;
            }
            
            this.canvas.applyTransformParams(this.scale, this.transX, this.transY);
        },
        
        makeDraggable: function(){
            var mouseDown = false;
            var oldPageX, oldPageY;
            var self = this;
            this.container.mousemove(function(e){
                if (mouseDown) {
                    var curTransX = self.transX;
                    var curTransY = self.transY;
                    
                    self.transX -= (oldPageX - e.pageX) / self.scale;
                    self.transY -= (oldPageY - e.pageY) / self.scale;
                    
                    self.applyTransform();
                    
                    oldPageX = e.pageX;
                    oldPageY = e.pageY;
                }
                return false;
            }).mousedown(function(e){
                mouseDown = true;
                oldPageX = e.pageX;
                oldPageY = e.pageY;
                return false;
            }).mouseup(function(){
                mouseDown = false;
                return false;
            });    
        },
        
        bindZoomButtons: function() {
            var map = this;
            var sliderDelta = ($('#zoom').innerHeight() - 6*2 - 15*2 - 3*2 - 7 - 6) / (this.zoomMaxStep - this.zoomCurStep);
            this.container.find('.jvectormap-zoomin').click(function(){
                if (map.zoomCurStep < map.zoomMaxStep) {
                    var curTransX = map.transX;
                    var curTransY = map.transY;
                    var curScale = map.scale;
                    map.transX -= (map.width / map.scale - map.width / (map.scale * map.zoomStep)) / 2;
                    map.transY -= (map.height / map.scale - map.height / (map.scale * map.zoomStep)) / 2;
                    map.setScale(map.scale * map.zoomStep);
                    map.zoomCurStep++;
                    $('#zoomSlider').css('top', parseInt($('#zoomSlider').css('top')) - sliderDelta);
                }
            });
            this.container.find('.jvectormap-zoomout').click(function(){
                if (map.zoomCurStep > 1) {
                    var curTransX = map.transX;
                    var curTransY = map.transY;
                    var curScale = map.scale;
                    map.transX += (map.width / (map.scale / map.zoomStep) - map.width / map.scale) / 2;
                    map.transY += (map.height / (map.scale / map.zoomStep) - map.height / map.scale) / 2;
                    map.setScale(map.scale / map.zoomStep);
                    map.zoomCurStep--;
                    $('#zoomSlider').css('top', parseInt($('#zoomSlider').css('top')) + sliderDelta);
                }
            });
        },
        
        setScale: function(scale) {
            this.scale = scale;
            this.applyTransform();
        },
        
        getCountryPath: function(cc) {
            return $('#'+cc)[0];
        }    
    }
	
	WorldMap.xlink = "http://www.w3.org/1999/xlink";
	WorldMap.mapIndex = 1;
    WorldMap.maps = {};
	
	var ColorScale = function(colors, normalizeFunction, minValue, maxValue) {
		if (colors) this.setColors(colors);
		if (normalizeFunction) this.setNormalizeFunction(normalizeFunction);
		if (minValue) this.setMin(minValue);
		if (minValue) this.setMax(maxValue);
	}
	
	ColorScale.prototype = {
		colors: [],
		
		setMin: function(min) {
			this.clearMinValue = min;
			if (typeof this.normalize === 'function') {
				this.minValue = this.normalize(min);
			} else {
				this.minValue = min;	
			}
		},
		
		setMax: function(max) {
			this.clearMaxValue = max;
			if (typeof this.normalize === 'function') {
				this.maxValue = this.normalize(max);
			} else {
				this.maxValue = max;	
			}
		},
		
		setColors: function(colors) {
			for (var i=0; i<colors.length; i++) {
				colors[i] = ColorScale.rgbToArray(colors[i]);
			}
			this.colors = colors;
		},
		
		setNormalizeFunction: function(f) {
			if (f === 'polynomial') {
				this.normalize = function(value) {
					return Math.pow(value, 0.2);
				}
			} else if (f === 'linear') {
				delete this.normalize;
			} else {
				this.normalize = f;
			}
			this.setMin(this.clearMinValue);
			this.setMax(this.clearMaxValue);
		},
		
		getColor: function(value) {
			if (typeof this.normalize === 'function') {
				value = this.normalize(value);	
			}
			var lengthes = [];
			var fullLength = 0;
			var l;
			for (var i=0; i<this.colors.length-1; i++) {
				l = this.vectorLength(this.vectorSubtract(this.colors[i+1], this.colors[i]));
				lengthes.push(l);
				fullLength += l;
			}
			var c = (this.maxValue - this.minValue) / fullLength;
			for (i=0; i<lengthes.length; i++) {
				lengthes[i] *= c;
			}
			i = 0;
			value -= this.minValue;
			while (value - lengthes[i] >= 0) {
				value -= lengthes[i];
				i++;
			}
			var color;
			if (i == this.colors.length - 1) {
				color = this.vectorToNum(this.colors[i]).toString(16);
			} else {
				color = (
					this.vectorToNum(
						this.vectorAdd(this.colors[i],
							this.vectorMult(
								this.vectorSubtract(this.colors[i+1], this.colors[i]),
								(value) / (lengthes[i])
							)
						)
					)
				).toString(16);
			}
			
			while (color.length < 6) {
				color = '0' + color;
			}
			return '#'+color;
		},
		
		vectorToNum: function(vector) {
			var num = 0;
			for (var i=0; i<vector.length; i++) {
				num += Math.round(vector[i])*Math.pow(256, vector.length-i-1);
			}
			return num;
		},
		
		vectorSubtract: function(vector1, vector2) {
			var vector = [];
			for (var i=0; i<vector1.length; i++) {
				vector[i] = vector1[i] - vector2[i];
			}
			return vector;
		},
		
		vectorAdd: function(vector1, vector2) {
			var vector = [];
			for (var i=0; i<vector1.length; i++) {
				vector[i] = vector1[i] + vector2[i];
			}
			return vector;
		},
		
		vectorMult: function(vector, num) {
			var result = [];
			for (var i=0; i<vector.length; i++) {
				result[i] = vector[i] * num;
			}
			return result;
		},
		
		vectorLength: function(vector) {
			var result = 0;
			for (var i=0; i<vector.length; i++) {
				result += vector[i]*vector[i];
			}
			return Math.sqrt(result);
		}
	}
	
	ColorScale.arrayToRgb = function(ar) {
		var rgb = '#';
		var d;
		for (var i=0; i<ar.length; i++) {
			d = ar[i].toString(16);
			rgb += d.length == 1 ? '0'+d : d;
		}
		return rgb;
	}
	
	ColorScale.rgbToArray = function(rgb) {
		rgb = rgb.substr(1);
		return [parseInt(rgb.substr(0, 2), 16), parseInt(rgb.substr(2, 2), 16), parseInt(rgb.substr(4, 2), 16)];
	}
})( jQuery );
