Array.prototype.contains = function(obj) {
    var i = this.length;
    while (i--) {
        if (this[i] === obj) {
            return true;
        }
    }
    return false;
}


function SankeyDisplay(element) {

var _this = this;

this.slider = null;

this.container = element;

this.margin = {top: 1, right: 1, bottom: 6, left: 1},
    this.width = 1260 - this.margin.left - this.margin.right,
    this.height = 800 - this.margin.top - this.margin.bottom,
    this.breadth = 0;

this.formatNumber = d3.format(",.0f"),
    this.format = function(d) { return formatNumber(d) + " TWh"; },
    this.color = d3.scale.category20();

this.svg = null;

this.sankey = null; //d3.sankey()
    //.nodeWidth(15)
    //.nodePadding(10)
    //.size([this.width, this.height]);

this.path = null; //this.sankey.link();

this.marriageUnitColor = function(level) {
    if (level == 0) return "#375C37";
    if (level == 1) return "#5C995C";
    if (level == 2) return "#9DC29D";
    return "#DEEBDE";
}

this.personHeight = function() {
      return function(d) { //console.log(d);
            var r = Math.max(d.dy, _this.sankey.nodeWidth()); 
            d.height = r/2;
            return d.height;
      };
};
    
this.drawTimeSlider = function(element) {

            // Add the time slider
            var min = 1800, max = 1900;
            var time_slider_scale = d3.scale.linear().domain([min, max]).range([min, max]);
            var time_slider_axis = d3.svg.axis().orient("bottom").ticks(20).scale(time_slider_scale).tickFormat(d3.format(".0f"));
            _this.slider = d3.slider().axis(time_slider_axis).min(min).max(max).on("slide", _this.temporalHighlight);
            d3.select(element).append("div").style("margin-top", "15px").style("margin-bottom", "20px").attr("id", "sliderDiv").call(_this.slider);
            
            //var stepperdiv = d3.select(element).append("div").style("margin-top", "30px");
            //stepperdiv.append("button").text("Prev").on("click", _this.goPrevious);
            //stepperdiv.append("button").text("All Time").on("click", _this.allTime);
            //stepperdiv.append("button").text("Next").on("click", _this.goNext);

    }

    this.goPrevious = function(event, time) {
        _this.slider.value(_this.slider.value() - 1);
        _this.slider.redraw();
        _this.temporalHighlight(null, _this.slider.value());
    }
    this.allTime = function(event, time) {
        _this.temporalHighlight(null,null);
    }
    this.goNext = function(event, time) {
        _this.slider.value(_this.slider.value() + 1);
        _this.slider.redraw();
        _this.temporalHighlight(null, _this.slider.value());
    }

this.temporalHighlight = function(event, time) {
    var timestr = time + "-01-01";
    console.log("updating to timestr " + timestr);
    _this.svg.selectAll(".link")
    .filter(function(d) { if(d.start <= timestr && d.end >= timestr) {return true;} else return false; })
    .style("opacity", 1);
    _this.svg.selectAll(".link")
    .filter(function(d) { return d.start > timestr || d.end < timestr })
    .style("opacity", 0);
    _this.svg.selectAll(".node")
    .filter(function(d) { return d.start <= timestr && d.end >= timestr })
    .style("opacity", 1);
    _this.svg.selectAll(".node")
    .filter(function(d) { return d.start > timestr || d.end < timestr })
    .style("opacity", 0);
    _this.svg.selectAll(".link")
    .filter(function(d) { return d.start =='' || d.end == '' })
    .style("opacity", 0.25);
    _this.svg.selectAll(".node")
    .filter(function(d) { return d.start == '' || d.end == '' })
    .style("opacity", 0.25);
    //$(".node").each(function() {
    //    console.log($(this));
    //});
}

this.drawDiagram = function(json_location) {

d3.json(json_location, function(jsonData) {

    // We must clean up the data now
    _this.marriages = jsonData.marriageUnits;
    _this.people = jsonData.people;
    _this.links = new Array();
    // Count in and out edges from each marriage unit
    _this.marriages.forEach(function(mu, index) {
        mu.orig_index = index;
        mu.inCount = 0;
        mu.outCount = 0;
        mu.type = "marriage";
        _this.people.forEach(function(person, pindex) {
            person.orig_index = pindex;
            person.inCount = 0;
            person.outCount = 0;
            person.type = "person";
            if (person.source.contains(mu.id)) { // person is from this MU
                mu.outCount++;
                if (!person.sourceMU) person.sourceMU = new Array();
                person.sourceMU.push(mu);
                if (!person.sources) person.sources = new Array();
                person.sources.push(index);
            }
            if (person.target.contains(mu.id)) { // person goes to this MU
                mu.inCount++;
                if (!person.targetMU) person.targetMU = new Array();
                person.targetMU.push(mu);
                if (!person.targets) person.targets = new Array();
                person.targets.push(index);
            }
        });
        if (mu.inCount > 0)
            mu.inPerc = 1 / mu.inCount;
        else
            mu.inPerc = 0;
        if (mu.outCount > 0)
            mu.outPerc = 1 / mu.outCount;
        else
            mu.outPerc = 0;
    });
    
    // Now, we set the marriages as nodes
    _this.nodes = _this.marriages.slice(0); // make a deep copy of the array
    var i = _this.nodes.length;
    _this.people.forEach(function(person) {
        // add the person to the list of nodes
        _this.nodes[i] = person;
        _this.nodes[i].start = person.birthdate;
        _this.nodes[i].end = person.deathdate;

        // add an edge for each source to this person
        person.sources.forEach(function(src) {
            var edge = {};
            edge.source = src;
            edge.sourcePerc = _this.nodes[src].outPerc;
            edge.target = i;
            edge.targetPerc = 1;
            edge.gender = person.gender;
            edge.start = person.birthdate;
            edge.end = person.deathdate;
            _this.links.push(edge);
        });
        // add an edge for each target from this person
        person.targets.forEach(function(tgt, idx) {
            var edge = {};
            edge.source = i;
            edge.sourcePerc = 1;
            edge.target = tgt;
            edge.targetPerc = _this.nodes[tgt].inPerc;
            edge.gender = person.gender;
            edge.start = person.birthdate;
            edge.end = person.deathdate;
            var realTgt = person.targetMU[idx].id;
            if (person.marriages && person.marriages[realTgt]) {
                if (person.marriages[realTgt].marriagedate)
                    edge.start = person.marriages[realTgt].marriagedate;
                if (person.marriages[realTgt].canceldate)
                    edge.end = person.marriages[realTgt].canceldate;
                if (person.marriages[realTgt].divorcedate)
                    edge.end = person.marriages[realTgt].divorcedate;
            }
            _this.links.push(edge);
        });
        
        i++; // increment index into nodes
    });




    //console.log(_this);
    _this.links.forEach(function(edge) {
        edge.value = 1; // needed for the sankey layout.  Not actually used
        edge.svalue = edge.sourcePerc;
        edge.tvalue = edge.targetPerc;
    });

    //console.log(_this.nodes);
    //console.log(_this.links);
  _this.sankey = d3.sankey()
      .nodes(_this.nodes)
      .links(_this.links)
      .size([_this.width, _this.height])
      .nodeBreadth(_this.breadth)
      .layout(350);

  _this.path = _this.sankey.link();
     //console.log(_this.sankey.nodes);
  _this.sankey.relayout();


  // Clean out the element
  d3.select(_this.container).text("");

  var sanksize = _this.sankey.size();
  _this.height = sanksize[1];
  _this.width = sanksize[2];

  _this.svg = d3.select(element).append("svg")
      .attr("width", sanksize[0] + _this.margin.left + _this.margin.right)
      .attr("height",sanksize[1] + _this.margin.top + _this.margin.bottom)
    .append("g")
      .attr("transform", "translate(" + _this.margin.left + "," + _this.margin.top + ")");



  var link = _this.svg.append("g").selectAll(".link")
      .data(_this.links)
    .enter().append("path")
      .attr("class", "link")
      .attr("d", _this.path)
      .style("stroke-width", _this.personHeight() ) //function(d) { return Math.max(d.sdy, d.tdy) / 2; })
      .style("stroke", function(d) { if (d.gender === "Male") return '#1D5190'; return '#C33742';})
      .sort(function(a, b) { return b.dy - a.dy; });
      
  _this.link = link;

/*  link.append("title")
     .text(function(d) { return d.name; });*/

  var node = _this.svg.append("g").selectAll(".node")
      .data(_this.nodes)
    .enter().append("g")
      .attr("class", "node")
      .attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; })
    .call(d3.behavior.drag()
      .origin(function(d) { return d; })
      .on("dragstart", function() { this.parentNode.appendChild(this); })
      .on("drag", dragmove))
      .on("mouseover", function(d, i) {//console.log(d); console.log(i);
          if (true) { //d.type === "person") {
            d3.selectAll(".link").filter( function(l) { 
                var nextEl = (l.source === d || l.target === d);
                var elAfter = false;
                if (d.type !== "person") {
                    if (l.source && l.source.targetLinks)
                       l.source.targetLinks.forEach(function (each) {
                            if (each.source === d) elAfter = true;   
                       });
                    if (l.target && l.target.sourceLinks)
                       l.target.sourceLinks.forEach(function (each) {
                            if (each.target === d) elAfter = true;   
                       });
                }
                // looking backwards:
                //   l.target = the target of the edge
                //   l.target.sourceLinks = list of edges that this target is the source of
                //   l.target.sourceLinks[...].target = target of the target
                
                return (nextEl || elAfter) ? this : null; 
            })
               .style("stroke-opacity", "0.8");
            //this.style("fill-opacity", "0.8");
          } })
      .on("mouseout", function(d, i) {//console.log(d); console.log(i);
          if (true) { //d.type === "person") {
            d3.selectAll(".link").filter( function(l) { 
                var nextEl = (l.source === d || l.target === d);
                var elAfter = false;
                if (d.type !== "person") {
                    if (l.source && l.source.targetLinks)
                       l.source.targetLinks.forEach(function (each) {
                            if (each.source === d) elAfter = true;   
                       });
                    if (l.target && l.target.sourceLinks)
                       l.target.sourceLinks.forEach(function (each) {
                            if (each.target === d) elAfter = true;   
                       });
                }
                // looking backwards:
                //   l.target = the target of the edge
                //   l.target.sourceLinks = list of edges that this target is the source of
                //   l.target.sourceLinks[...].target = target of the target
                
                return (nextEl || elAfter) ? this : null; 
            })
               .style("stroke-opacity", "");
          } });

  // Draw the marriage nodes
  node.filter(function(d) { return (d.type === "marriage") ? this : null;}).append("circle")
      .attr("r", function(d) { //console.log(d);
            var r = 0; 
            //r = d.dy / 2;
            r = Math.max(d.dy, _this.sankey.nodeWidth()) / 2;
            return r;
      })
      .attr("cy", function(d) { return d.dy / 2; })
      .attr("cx", function(d) { return _this.sankey.nodeWidth() / 2; })
      .style("fill", function(d) { //console.log(d); 
          return d.color = _this.marriageUnitColor(d.level);   
          //return d.color = "#bbbbbb"; /* "#D0A9F5"; color(d.name.replace( .*, ""));*/ 
          })
      .style("stroke", function(d) { return d3.rgb(d.color).darker(2); })
      .on("click", show_info)

   // Draw the person nodes
   node.filter(function(d) { return (d.type === "person") ? this : null;}).append("rect")
      .attr("height", _this.personHeight()) 
      /*
       * function(d) { //console.log(d);
            var r = Math.max(d.dy, _this.sankey.nodeWidth()); 
            d.height = r/2;
            return d.height;
      })*/
      .attr("width", function(d) { //console.log(d);
            return d.height;
      })
      .attr("y", function(d) { return d.dy / 2 - (d.height / 2); })
      .attr("x", function(d) { return _this.sankey.nodeWidth() / 2 - (d.height / 2); })
      .style("fill", function(d) { //console.log(d); 
             var fill;
             if (d.gender === "Male")
                 fill = '#1D5190';
             else
                 fill = '#C33742';
             return d.color = fill; /* "#D0A9F5"; color(d.name.replace( .*, ""));*/ })
      .style("stroke", function(d) { return d.color; })
      .style("stroke-opacity", "0.5")
      .style("fill-opacity","0.2")
      .on("click", function() {/*console.log(_this);*/});
//    .append("title")
//      .text(function(d) { return d.name + "\n" + format(d.value); });
/*
  node.append("text")
      .attr("x", function(d) { return (_this.sankey.nodeWidth() / 2);})
      .attr("y", function(d) { return d.dy / 2; })
      .attr("dy", ".35em")
      .attr("text-anchor", "middle")
      .attr("transform", null)
      .text(function(d) { return (d.type === "person") ? d.name : ""; });
    /*.filter(function(d) { return d.x < width / 2; })
      .attr("x", 6 + sankey.nodeWidth())
      .attr("text-anchor", "start");*/
  $('.node').tipsy({ 
        gravity: 'c', 
        html: true, 
        offset: 8,
        hoverlock: true,
        title: function() {
          var d = this.__data__;
          return (d.type === "marriage" && d.name) ? d.name + "'s Marriage" : d.name; 
        }
      });

  function dragmove(d) {
    d3.select(this).attr("transform", "translate(" + d.x + "," + (d.y = Math.max(0, Math.min(_this.height - d.dy, d3.event.y))) + ")");
    _this.sankey.relayout();
    link.attr("d", _this.path);
  }
});

}; // end drawDiagram

} // end SankeyDisplay
