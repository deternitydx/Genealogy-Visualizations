
function SankeyDisplay(element) {

var _this = this;

this.margin = {top: 1, right: 1, bottom: 6, left: 1},
    this.width = 960 - this.margin.left - this.margin.right,
    this.height = 500 - this.margin.top - this.margin.bottom;

this.formatNumber = d3.format(",.0f"),
    this.format = function(d) { return formatNumber(d) + " TWh"; },
    this.color = d3.scale.category20();

this.svg = d3.select(element).append("svg")
    .attr("width", this.width + this.margin.left + this.margin.right)
    .attr("height", this.height + this.margin.top + this.margin.bottom)
  .append("g")
    .attr("transform", "translate(" + this.margin.left + "," + this.margin.top + ")");

this.sankey = d3.sankey()
    .nodeWidth(15)
    .nodePadding(10)
    .size([this.width, this.height]);

this.path = this.sankey.link();

this.drawDiagram = function(json_location) {

d3.json(json_location, function(energy) {

  _this.sankey
      .nodes(energy.nodes)
      .links(energy.links)
      .layout(32);

  var link = _this.svg.append("g").selectAll(".link")
      .data(energy.links)
    .enter().append("path")
      .attr("class", "link")
      .attr("d", _this.path)
      .style("stroke-width", function(d) { return Math.min(d.sdy, d.tdy); })
      .style("stroke", function(d) { if (d.type === 1) return '#1D5190'; return '#C33742';})
      .sort(function(a, b) { return b.dy - a.dy; });
      
  $('.link').tipsy({ 
        gravity: 'c', 
        html: true, 
        offset: 0,
        hoverlock: true,
        title: function() {
          var d = this.__data__;
          return d.name; 
        }
      });

/*  link.append("title")
     .text(function(d) { return d.name; });*/

  var node = _this.svg.append("g").selectAll(".node")
      .data(energy.nodes)
    .enter().append("g")
      .attr("class", "node")
      .attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; })
      .on("click", show_info)
    .call(d3.behavior.drag()
      .origin(function(d) { return d; })
      .on("dragstart", function() { this.parentNode.appendChild(this); })
      .on("drag", dragmove));
      
   var circle = node.append("circle")
      .attr("r", function(d) { return d.dy / 2; })
      .attr("cy", function(d) { return d.dy / 2; })
      .attr("cx", function(d) { return _this.sankey.nodeWidth() / 2; })
      .style("fill", function(d) { return d.color = "#bbbbbb"; })
      .style("stroke", function(d) { return d3.rgb(d.color).darker(2); })
      .on("click", show_info);
      
   console.log(node);
   circle.each(function (d,i) {
   		console.log(d);
   		console.log(i);
   		console.log(node[0][i]);
   	   var cd = new ChordDisplay(node[0][i]);
  		cd.embed = true;
  		cd.width = d.dy;
  		cd.height = d.dy;
  		cd.drawChord(d.name);
   });
      
  
/*

//    .append("title")
//      .text(function(d) { return d.name + "\n" + format(d.value); });

  node.append("text")
      .attr("x", function(d) { return (_this.sankey.nodeWidth() / 2);})
      .attr("y", function(d) { return d.dy / 2; })
      .attr("dy", ".35em")
      .attr("text-anchor", "middle")
      .attr("transform", null)
      .text(function(d) { return d.name; });
  */

  function dragmove(d) {
    d3.select(this).attr("transform", "translate(" + d.x + "," + (d.y = Math.max(0, Math.min(_this.height - d.dy, d3.event.y))) + ")");
    _this.sankey.relayout();
    link.attr("d", _this.path);
  }
});

}; // end drawDiagram

} // end SankeyDisplay
