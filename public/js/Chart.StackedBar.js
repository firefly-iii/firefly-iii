(function (factory) {
	"use strict";
	if (typeof define === 'function' && define.amd) {
		// AMD. Register as an anonymous module.
		define(['chart.js'], factory);
	} else if (typeof exports === 'object') {
		// Node/CommonJS
		module.exports = factory(require('chart.js'));
	} else {
		// Global browser
		factory(Chart);
	}
}(function (Chart) {
	"use strict";

	var helpers = Chart.helpers;

	var defaultConfig = {
		scaleBeginAtZero : true,

		//Boolean - Whether grid lines are shown across the chart
		scaleShowGridLines : true,

		//String - Colour of the grid lines
		scaleGridLineColor : "rgba(0,0,0,.05)",

		//Number - Width of the grid lines
		scaleGridLineWidth : 1,

        //Boolean - Whether to show horizontal lines (except X axis)
		scaleShowHorizontalLines: true,

		//Boolean - Whether to show vertical lines (except Y axis)
		scaleShowVerticalLines: true,

		//Boolean - If there is a stroke on each bar
		barShowStroke : true,

		//Number - Pixel width of the bar stroke
		barStrokeWidth : 2,

		//Number - Spacing between each of the X value sets
		barValueSpacing : 5,

		//Boolean - Whether bars should be rendered on a percentage base
		relativeBars : false,

		//String - A legend template
		legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].fillColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>",

		//Boolean - Show total legend
		showTotal: false,

		//String - Color of total legend
		totalColor: '#fff',

		//String - Total Label
		totalLabel: 'Total',

		//Boolean - Hide labels with value set to 0
		tooltipHideZero: false
	};

	Chart.Type.extend({
		name: "StackedBar",
		defaults : defaultConfig,
		initialize:  function(data){
			//Expose options as a scope variable here so we can access it in the ScaleClass
			var options = this.options;

			// Save data as a source for updating of values & methods
			this.data = data;

			this.ScaleClass = Chart.Scale.extend({
				offsetGridLines : true,
				calculateBarX : function(barIndex){
					return this.calculateX(barIndex);
				},
				calculateBarY : function(datasets, dsIndex, barIndex, value){
					var offset = 0,
						sum = 0;

					for(var i = 0; i < datasets.length; i++) {
						sum += datasets[i].bars[barIndex].value;
					}
					for(i = dsIndex; i < datasets.length; i++) {
						if(i === dsIndex && value) {
							offset += value;
						} else {
							offset = +offset + +datasets[i].bars[barIndex].value;
						}
					}

					if(options.relativeBars) {
						offset = offset / sum * 100;
					}

					return this.calculateY(offset);
				},
				calculateBaseWidth : function(){
					return (this.calculateX(1) - this.calculateX(0)) - (2*options.barValueSpacing);
				},
				calculateBaseHeight : function(){
					return (this.calculateY(1) - this.calculateY(0));
				},
				calculateBarWidth : function(datasetCount){
					//The padding between datasets is to the right of each bar, providing that there are more than 1 dataset
					return this.calculateBaseWidth();
				},
				calculateBarHeight : function(datasets, dsIndex, barIndex, value) {
					var sum = 0;

					for(var i = 0; i < datasets.length; i++) {
						sum += datasets[i].bars[barIndex].value;
					}

					if(!value) {
						value = datasets[dsIndex].bars[barIndex].value;
					}

					if(options.relativeBars) {
						value = value / sum * 100;
					}

					return this.calculateY(value);
				}
			});

			this.datasets = [];

			//Set up tooltip events on the chart
			if (this.options.showTooltips){
				helpers.bindEvents(this, this.options.tooltipEvents, function(evt){
					var activeBars = (evt.type !== 'mouseout') ? this.getBarsAtEvent(evt) : [];

					this.eachBars(function(bar){
						bar.restore(['fillColor', 'strokeColor']);
					});
					helpers.each(activeBars, function(activeBar){
						activeBar.fillColor = activeBar.highlightFill;
						activeBar.strokeColor = activeBar.highlightStroke;
					});
					this.showTooltip(activeBars);
				});
			}

			//Declare the extension of the default point, to cater for the options passed in to the constructor
			this.BarClass = Chart.Rectangle.extend({
				strokeWidth : this.options.barStrokeWidth,
				showStroke : this.options.barShowStroke,
				ctx : this.chart.ctx
			});

			//Iterate through each of the datasets, and build this into a property of the chart
			helpers.each(data.datasets,function(dataset,datasetIndex){

				var datasetObject = {
					label : dataset.label || null,
					fillColor : dataset.fillColor,
					strokeColor : dataset.strokeColor,
					bars : []
				};

				this.datasets.push(datasetObject);

				helpers.each(dataset.data,function(dataPoint,index){
					if(!helpers.isNumber(dataPoint)){
						dataPoint = 0;
					}
					//Add a new point for each piece of data, passing any required data to draw.
					//Add 0 as value if !isNumber (e.g. empty values are useful when 0 values should be hidden in tooltip)
					datasetObject.bars.push(new this.BarClass({
						value : dataPoint,
						label : data.labels[index],
						datasetLabel: dataset.label,
						strokeColor : dataset.strokeColor,
						fillColor : dataset.fillColor,
						highlightFill : dataset.highlightFill || dataset.fillColor,
						highlightStroke : dataset.highlightStroke || dataset.strokeColor
					}));
				},this);

			},this);

			this.buildScale(data.labels);

			this.eachBars(function(bar, index, datasetIndex){
				helpers.extend(bar, {
					base: this.scale.endPoint,
					height: 0,
					width : this.scale.calculateBarWidth(this.datasets.length),
					x: this.scale.calculateBarX(index),
					y: this.scale.endPoint
				});
				bar.save();
			}, this);

			this.render();
		},
		showTooltip : function(ChartElements, forceRedraw){
			// Only redraw the chart if we've actually changed what we're hovering on.
			if (typeof this.activeElements === 'undefined') this.activeElements = [];

			helpers = Chart.helpers;

			var isChanged = (function(Elements){
				var changed = false;

				if (Elements.length !== this.activeElements.length){
					changed = true;
					return changed;
				}

				helpers.each(Elements, function(element, index){
					if (element !== this.activeElements[index]){
						changed = true;
					}
				}, this);
				return changed;
			}).call(this, ChartElements);

			if (!isChanged && !forceRedraw){
				return;
			}
			else{
				this.activeElements = ChartElements;
			}
			this.draw();
			if(this.options.customTooltips){
				this.options.customTooltips(false);
			}
			if (ChartElements.length > 0){
				// If we have multiple datasets, show a MultiTooltip for all of the data points at that index
				if (this.datasets && this.datasets.length > 1) {
					var dataArray,
					dataIndex;

					for (var i = this.datasets.length - 1; i >= 0; i--) {
						dataArray = this.datasets[i].points || this.datasets[i].bars || this.datasets[i].segments;
						dataIndex = helpers.indexOf(dataArray, ChartElements[0]);
						if (dataIndex !== -1){
							break;
						}
					}
					var tooltipLabels = [],
					tooltipColors = [],
					medianPosition = (function(index) {

						// Get all the points at that particular index
						var Elements = [],
						dataCollection,
						xPositions = [],
						yPositions = [],
						xMax,
						yMax,
						xMin,
						yMin;
						helpers.each(this.datasets, function(dataset){
							dataCollection = dataset.points || dataset.bars || dataset.segments;
							if (dataCollection[dataIndex] && dataCollection[dataIndex].hasValue()){
								Elements.push(dataCollection[dataIndex]);
							}
						});

						var total = {
							datasetLabel: this.options.totalLabel,
							value: 0,
							fillColor: this.options.totalColor,
							strokeColor: this.options.totalColor
						};

						helpers.each(Elements, function(element) {
							if (this.options.tooltipHideZero && element.value === 0) {
								return;
							}

							xPositions.push(element.x);
							yPositions.push(element.y);

							total.value += element.value;

							//Include any colour information about the element
							tooltipLabels.push(helpers.template(this.options.multiTooltipTemplate, element));
							tooltipColors.push({
								fill: element._saved.fillColor || element.fillColor,
								stroke: element._saved.strokeColor || element.strokeColor
							});

						}, this);

						if (this.options.showTotal) {
							tooltipLabels.push(helpers.template(this.options.multiTooltipTemplate, total));
							tooltipColors.push({
								fill: total.fillColor,
								stroke: total.strokeColor
							});
						}

						yMin = helpers.min(yPositions);
						yMax = helpers.max(yPositions);

						xMin = helpers.min(xPositions);
						xMax = helpers.max(xPositions);

						return {
							x: (xMin > this.chart.width/2) ? xMin : xMax,
							y: (yMin + yMax)/2
						};
					}).call(this, dataIndex);

					new Chart.MultiTooltip({
						x: medianPosition.x,
						y: medianPosition.y,
						xPadding: this.options.tooltipXPadding,
						yPadding: this.options.tooltipYPadding,
						xOffset: this.options.tooltipXOffset,
						fillColor: this.options.tooltipFillColor,
						textColor: this.options.tooltipFontColor,
						fontFamily: this.options.tooltipFontFamily,
						fontStyle: this.options.tooltipFontStyle,
						fontSize: this.options.tooltipFontSize,
						titleTextColor: this.options.tooltipTitleFontColor,
						titleFontFamily: this.options.tooltipTitleFontFamily,
						titleFontStyle: this.options.tooltipTitleFontStyle,
						titleFontSize: this.options.tooltipTitleFontSize,
						cornerRadius: this.options.tooltipCornerRadius,
						labels: tooltipLabels,
						legendColors: tooltipColors,
						legendColorBackground : this.options.multiTooltipKeyBackground,
						title: ChartElements[0].label,
						chart: this.chart,
						ctx: this.chart.ctx,
						custom: this.options.customTooltips
					}).draw();

				} else {
					helpers.each(ChartElements, function(Element) {
						var tooltipPosition = Element.tooltipPosition();
						new Chart.Tooltip({
							x: Math.round(tooltipPosition.x),
							y: Math.round(tooltipPosition.y),
							xPadding: this.options.tooltipXPadding,
							yPadding: this.options.tooltipYPadding,
							fillColor: this.options.tooltipFillColor,
							textColor: this.options.tooltipFontColor,
							fontFamily: this.options.tooltipFontFamily,
							fontStyle: this.options.tooltipFontStyle,
							fontSize: this.options.tooltipFontSize,
							caretHeight: this.options.tooltipCaretSize,
							cornerRadius: this.options.tooltipCornerRadius,
							text: helpers.template(this.options.tooltipTemplate, Element),
							chart: this.chart,
							custom: this.options.customTooltips
						}).draw();
					}, this);
				}
			}
			return this;
		},
		update : function(){

			//Iterate through each of the datasets, and build this into a property of the chart
			helpers.each(this.data.datasets,function(dataset,datasetIndex){

				helpers.extend(this.datasets[datasetIndex], {
					label : dataset.label || null,
					fillColor : dataset.fillColor,
					strokeColor : dataset.strokeColor,
				});

				helpers.each(dataset.data,function(dataPoint,index){
					helpers.extend(this.datasets[datasetIndex].bars[index], {
						value : dataPoint,
						label : this.data.labels[index],
						datasetLabel: dataset.label,
						strokeColor : dataset.strokeColor,
						fillColor : dataset.fillColor,
						highlightFill : dataset.highlightFill || dataset.fillColor,
						highlightStroke : dataset.highlightStroke || dataset.strokeColor
					});
				},this);

			},this);


			this.scale.update();
			// Reset any highlight colours before updating.
			helpers.each(this.activeElements, function(activeElement){
				activeElement.restore(['fillColor', 'strokeColor']);
			});

			this.eachBars(function(bar){
				bar.save();
			});
			this.render();
		},
		eachBars : function(callback){
			helpers.each(this.datasets,function(dataset, datasetIndex){
				helpers.each(dataset.bars, callback, this, datasetIndex);
			},this);
		},
		getBarsAtEvent : function(e){
			var barsArray = [],
				eventPosition = helpers.getRelativePosition(e),
				datasetIterator = function(dataset){
					barsArray.push(dataset.bars[barIndex]);
				},
				barIndex;

			for (var datasetIndex = 0; datasetIndex < this.datasets.length; datasetIndex++) {
				for (barIndex = 0; barIndex < this.datasets[datasetIndex].bars.length; barIndex++) {
					if (this.datasets[datasetIndex].bars[barIndex].inRange(eventPosition.x,eventPosition.y)){
						helpers.each(this.datasets, datasetIterator);
						return barsArray;
					}
				}
			}

			return barsArray;
		},
		buildScale : function(labels){
			var self = this;

			var dataTotal = function(){
				var values = [];
				helpers.each(self.datasets, function(dataset) {
					helpers.each(dataset.bars, function(bar, barIndex) {
						if(!values[barIndex]) values[barIndex] = 0;
						if(self.options.relativeBars) {
							values[barIndex] = 100;
						} else {
							values[barIndex] = +values[barIndex] + +bar.value;
						}
					});
				});
				return values;
			};

			var scaleOptions = {
				templateString : this.options.scaleLabel,
				height : this.chart.height,
				width : this.chart.width,
				ctx : this.chart.ctx,
				textColor : this.options.scaleFontColor,
				fontSize : this.options.scaleFontSize,
				fontStyle : this.options.scaleFontStyle,
				fontFamily : this.options.scaleFontFamily,
				valuesCount : labels.length,
				beginAtZero : this.options.scaleBeginAtZero,
				integersOnly : this.options.scaleIntegersOnly,
				calculateYRange: function(currentHeight){
					var updatedRanges = helpers.calculateScaleRange(
						dataTotal(),
						currentHeight,
						this.fontSize,
						this.beginAtZero,
						this.integersOnly
					);
					helpers.extend(this, updatedRanges);
				},
				xLabels : this.options.xLabels || labels,
				font : helpers.fontString(this.options.scaleFontSize, this.options.scaleFontStyle, this.options.scaleFontFamily),
				lineWidth : this.options.scaleLineWidth,
				lineColor : this.options.scaleLineColor,
				gridLineWidth : (this.options.scaleShowGridLines) ? this.options.scaleGridLineWidth : 0,
				gridLineColor : (this.options.scaleShowGridLines) ? this.options.scaleGridLineColor : "rgba(0,0,0,0)",
                showHorizontalLines : this.options.scaleShowHorizontalLines,
				showVerticalLines : this.options.scaleShowVerticalLines,
				padding : (this.options.showScale) ? 0 : (this.options.barShowStroke) ? this.options.barStrokeWidth : 0,
				showLabels : this.options.scaleShowLabels,
				display : this.options.showScale
			};

			if (this.options.scaleOverride){
				helpers.extend(scaleOptions, {
					calculateYRange: helpers.noop,
					steps: this.options.scaleSteps,
					stepValue: this.options.scaleStepWidth,
					min: this.options.scaleStartValue,
					max: this.options.scaleStartValue + (this.options.scaleSteps * this.options.scaleStepWidth)
				});
			}

			this.scale = new this.ScaleClass(scaleOptions);
		},
		addData : function(valuesArray,label){
			//Map the values array for each of the datasets
			helpers.each(valuesArray,function(value,datasetIndex){
				if (helpers.isNumber(value)){
					//Add a new point for each piece of data, passing any required data to draw.
					//Add 0 as value if !isNumber (e.g. empty values are useful when 0 values should be hidden in tooltip)
					this.datasets[datasetIndex].bars.push(new this.BarClass({
						value : helpers.isNumber(value)?value:0,
						label : label,
						x: this.scale.calculateBarX(this.scale.valuesCount+1),
						y: this.scale.endPoint,
						width : this.scale.calculateBarWidth(this.datasets.length),
						base : this.scale.endPoint,
						strokeColor : this.datasets[datasetIndex].strokeColor,
						fillColor : this.datasets[datasetIndex].fillColor
					}));
				}
			},this);

			this.scale.addXLabel(label);
			//Then re-render the chart.
			this.update();
		},
		removeData : function(){
			this.scale.removeXLabel();
			//Then re-render the chart.
			helpers.each(this.datasets,function(dataset){
				dataset.bars.shift();
			},this);
			this.update();
		},
		reflow : function(){
			helpers.extend(this.BarClass.prototype,{
				y: this.scale.endPoint,
				base : this.scale.endPoint
			});
			var newScaleProps = helpers.extend({
				height : this.chart.height,
				width : this.chart.width
			});
			this.scale.update(newScaleProps);
		},
		draw : function(ease){
			var easingDecimal = ease || 1;
			this.clear();

			var ctx = this.chart.ctx;

			this.scale.draw(easingDecimal);

			//Draw all the bars for each dataset
			helpers.each(this.datasets,function(dataset,datasetIndex){
				helpers.each(dataset.bars,function(bar,index){
					var y = this.scale.calculateBarY(this.datasets, datasetIndex, index, bar.value),
						height = this.scale.calculateBarHeight(this.datasets, datasetIndex, index, bar.value);

					//Transition then draw
					if(bar.value > 0) {
						bar.transition({
							base : this.scale.endPoint - (Math.abs(height) - Math.abs(y)),
							x : this.scale.calculateBarX(index),
							y : Math.abs(y),
							height : Math.abs(height),
							width : this.scale.calculateBarWidth(this.datasets.length)
						}, easingDecimal).draw();
					}
				},this);
			},this);
		}
	});
}));
