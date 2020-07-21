function ChordDisplay(element) {

    var _this = this;
    this.element = element;

    this.innerElement = null;
    
    this.originalTime = null;

    this.matrix = [];

    var getColor = function (gender, role) {
        if (gender === "Male" && role === "parent")
            return "#1D5190";
        if (gender === "Female" && role === "parent")
            return "#C33742";
        if (gender === "Male" && role === "child")
            return "#73A8E9";
        if (gender === "Female" && role === "child")
            return "#D6757D";
        if (gender === "Male" && role === "divorce")
            return "#0d233e";
        if (gender === "Female" && role === "divorce")
            return "#391013";
		if (role === "child") // unknown child gender is light purple
			return "#CC66FF";
        return "#9900CC"; //  unknown show up as purple
    }


    this.width = 600,
    this.height = 500,
    this.innerRadius = Math.min(this.width, this.height) * .31,
    this.outerRadius = this.innerRadius * 1.3,
    this.drawNumSigOthers = false;
    this.sigOtherElement = null;
    this.patriarchal = true;
    this.nameAsTitle = false;
    this.useHoverOver = false;
    this.hoverElement = null;
    this.placeHolder = {gender:"placeholder", id:"-1", name:"&nbsp;"};

    this.embed = false;

    this.slider = null;

    this.json_location = function(id) { 
        var noC = new String((new Date().getTime())); 
        return "test/" + id + ".json?nocache=" + noC;
    };

    this.drawTitle = function(element, title) {
        var titleE = d3.select(element).append("div");
        titleE.append("h1").text(title);
        
	var time = "All Time";
        if (_this.originalTime != null) {
            time = _this.originalTime;
        }
        
	titleE.append("h2").attr("id", "timeText").text(time);
        
        _this.sigOtherElement = titleE.append("h4");
    }

    this.updateNumSigOthers = function(element, numWives) {
        var type = ' wives';
        if (!_this.patriarchal)
             type = ' husbands';
        if (_this.sigOtherElement != null)
            _this.sigOtherElement.text(numWives + type);
        else
            element.append("div").append("h4").text(numWives + type);
    }

    this.drawLegend = function(element) {

        var cont = d3.select(element);

        cont.append("h3").text("Legend");

        cont.append("h4").text("People");
        var table = cont.append("table");
        table.append("tr").append("td").style("background", getColor("Male", "parent")).style("color", "#FFFFFF").text("Male Parent");
        table.append("tr").append("td").style("background", getColor("Male", "child")).style("color", "#FFFFFF").text("Male Child");
        table.append("tr").append("td").style("background", getColor("Female", "parent")).style("color", "#FFFFFF").text("Female Parent");
        table.append("tr").append("td").style("background", getColor("Female", "child")).style("color", "#FFFFFF").text("Female Child");

        cont.append("h4").text("Relations");
        var table = cont.append("table");
        table.append("tr").append("td").style("background", "#A1CB87").style("color", "#000000").text("Biological");
        table.append("tr").append("td").style("background", "#FFCD81").style("color", "#000000").text("Adoption");
        table.append("tr").append("td").style("background", "#C9BCD6").style("color", "#000000").text("Married (BYU)");
        table.append("tr").append("td").style("background", "#AD85FF").style("color", "#000000").text("Sealed (Eternity)");
        table.append("tr").append("td").style("background", "#f7fcb9").style("color", "#000000").text("Sealed (Time)");
        table.append("tr").append("td").style("background", "#FFB2E6").style("color", "#000000").text("Married (Civil)");
        /**
        var time = "All Time";
        if (_this.originalTime != null) {
            time = _this.originalTime;
        }
        cont.append("h4").text("Time Slice");
        var table = cont.append("table");
        table.append("tr").append("td").style("background", "#d8d8d8").style("color", "#000000").attr("id", "timeText").text(time);
    	**/
    };

    this.setMatrix = function(timepoint) {
        _this.timepoint = timepoint;    
        var children = _this.data.children;
        var parents = _this.data.parents;
        if (timepoint != null) { // we actually need to look into and build the structures
            children = new Array();
            parents = new Array();

            _this.data.parents.forEach(function(parent) {
                // This if statement below only adds those parents who are either the main gender of the relationship,
                // or a spouse that was married after this time point but the time point is before their death, and
                // they are not divorced before this time point.
                if (/*parent.birthDate <= timepoint && parent.deathDate >= timepoint &&*/
                    (parent.marriageDate <= timepoint && parent.deathDate >= timepoint 
                        && (parent.divorceDate >= timepoint || parent.divorceDate == "")) 
                        || (_this.patriarchal && parent.gender == "Male")
                        || (!_this.patriarchal && parent.gender == "Female")) { 
                        parents.push(parent);
                }
            });
            _this.data.children.forEach(function(child) {
                if ((child.adoptionDate != "" && child.adoptionDate <= timepoint) || // child has been adopted
                    (child.adoptionDate == "" && child.birthDate <= timepoint && child.deathDate >= timepoint)) // child is alive
                    children.push(child);
            });
        }

        if (parents.length == 0) {
            parents.push(_this.placeHolder);
        }

        if (children.length == 0) {
            children.push(_this.placeHolder);
        }

        _this.parents = parents;
        _this.children = children;
        _this.relationships = _this.data.relationships;

        _this.parPerc = 100.0 / parents.length;
        _this.chiPerc = 100.0 / children.length;

        _this.people = children.concat(parents);
        _this.numPeople = parents.length + children.length;


        _this.matrix = new Array();
        for (var i=0; i < _this.numPeople; i++) {
            _this.matrix[i] = new Array();
            for (var j=0; j < _this.numPeople; j++) {
                if (i === j) {
                    if (i < _this.children.length)          // first entries are _this.children
                        _this.matrix[i][j] = _this.chiPerc;
                    else                                // last entries are _this.parents
                        _this.matrix[i][j] = _this.parPerc;
                } else {
                    _this.matrix[i][j] = 0;         // right now, set the connections to none
                }
            }
        }

        // update the matrix based on the _this.relationships between _this.people
        _this.people.forEach(function(person) {
            person.numRels = 0;
        });

        _this.relationships.forEach(function (rel) {
            delete rel.fromId; delete rel.toId;
            _this.people.forEach(function(person, i) {
                if (rel.from === person.id) {
                    person.numRels++;
                    rel.fromId = i;
                }
                if (rel.to === person.id) {
                    person.numRels++;
                    rel.toId = i;
                }
            });
        });

        var activeRels = new Array();

        _this.relationships.forEach(function (rel) {
            // Fix up the peopl who are not in a relationship with a living person 
            if (!rel.hasOwnProperty('fromId') || !rel.hasOwnProperty('toId')) {
                if (!rel.hasOwnProperty('fromId') && rel.hasOwnProperty('toId'))
                    _this.people[rel.toId].numRels--;
                if (rel.hasOwnProperty('fromId') && !rel.hasOwnProperty('toId'))
                    _this.people[rel.fromId].numRels--;
                return;
            } else {
                // hack for now, to keep from seeing two different marriages

                activeRels.push(rel);
            }
        });

        _this.relationships = activeRels;

        _this.relationships.forEach(function (rel) {
            if (rel.hasOwnProperty('fromId') && rel.hasOwnProperty('toId')) {
    

                // for each relationship, add a part of the matrix
                var i = rel.fromId;
                var j = rel.toId;
    
                var iPerc = (i < _this.children.length) ? _this.chiPerc / _this.people[i].numRels : _this.parPerc / _this.people[i].numRels;
                var jPerc = (j < _this.children.length) ? _this.chiPerc / _this.people[j].numRels : _this.parPerc / _this.people[j].numRels;
    
                _this.matrix[i][i] -= iPerc;
                _this.matrix[j][j] -= jPerc;
                _this.matrix[i][j] += iPerc;
                _this.matrix[j][i] += jPerc;
            }   
        });



        // set up the colors properly based on the type of person
        _this.colorList = new Array();
        for (var i=0; i < _this.numPeople; i++) {
            if (i < _this.children.length)
                _this.colorList[i] = getColor(_this.children[i].gender, "child");
            else {
                var cur = _this.parents[i - _this.children.length];
                if (timepoint == null && cur.divorceDate != "")
                // use divorce method
                    _this.colorList[i] = getColor(cur.gender, "divorce");
                else
                    _this.colorList[i] = getColor(cur.gender, "parent");
            
            }//    _this.colorList[i] = getColor(_this.parents[i - _this.children.length].gender, "parent");
        }

    };

    this.draw = function() {

            _this.fill = d3.scale.ordinal()
            .domain(d3.range(7))
            .range(_this.colorList);

            _this.fillType = d3.scale.ordinal()
                 .domain(["adoption", "biological", "byu", "eternity", "time", "civil", "placeholder", "biological.adoption",  "civil.eternity", "civil.time", "civil.eternity.time", "civil.eternity.eternity"])
                 .range(["#FFCD81", "#A1CB87", "#C9BCD6", "#AD85FF", "#f7fcb9", "#FFB2E6", "#ffffff", "url(#biological-adoption)", "url(#civil-eternity)", "url(#civil-time)", "url(#civil-eternity-time)", "url(#civil-eternity)"]);


            _this.chord = d3.layout.chord()
            .padding(.01)
            .matrix(_this.matrix); 


            // If we are not embedding, then add an SVG to the element.  If we are, then just add to the element.
            _this.svg = null;
            if (_this.embed) {

                // recalculate the radii
                _this.innerRadius = Math.min(_this.width, _this.height) * .31,
                _this.outerRadius = Math.min(_this.width, _this.height) / 2;

                _this.svg = d3.select(_this.element)
                .append("g").attr("transform", "translate(7," + _this.height / 2 + ")");
            } else {
                // only draw the title once, if needed
                if (_this.innerElement == null) {
                    if (_this.nameAsTitle) 
                        _this.drawTitle(_this.element, _this.parents[_this.parents.length - 1].name);
                    _this.innerElement = d3.select(_this.element).append("div");
                }    
                _this.innerElement.html("");
                _this.svg = _this.innerElement.append("svg")
                .attr("width", _this.width)
                .attr("height", _this.height)
                .append("g")
                .attr("transform", "translate(" + _this.width / 2 + "," + _this.height / 2 + ")");
            }
            if (_this.drawNumSigOthers) {
                 _this.updateNumSigOthers(_this.innerElement, _this.parents.length - 1);
            }
            // placeholder for hoverover text
            if (!_this.useHoverOver) {
                var tmp = _this.innerElement.append("div").attr("class", "hoverinfo");
                tmp.append("h4").text("More Information");
                _this.hoverElement = tmp.append("div").attr("class", "hoverinfoinner").append("h5"); 
                _this.hoverElement.html("&nbsp;<br>&nbsp;");
            }

            _this.defs = _this.svg.append("defs");
            var tmpdef = _this.defs.append("linearGradient").attr("id","civil-eternity");
            tmpdef.append("stop").attr("offset", "0%").attr("stop-color", "#FFB2E6");
            tmpdef.append("stop").attr("offset", "20%").attr("stop-color", "#AD85FF");
            tmpdef.append("stop").attr("offset", "40%").attr("stop-color", "#FFB2E6");
            tmpdef.append("stop").attr("offset", "60%").attr("stop-color", "#AD85FF");
            tmpdef.append("stop").attr("offset", "80%").attr("stop-color", "#FFB2E6");
            tmpdef.append("stop").attr("offset", "100%").attr("stop-color", "#AD85FF");
            tmpdef = _this.defs.append("linearGradient").attr("id","civil-time");
            tmpdef.append("stop").attr("offset", "0%").attr("stop-color", "#FFB2E6");
            tmpdef.append("stop").attr("offset", "20%").attr("stop-color", "#f7fcb9");
            tmpdef.append("stop").attr("offset", "40%").attr("stop-color", "#FFB2E6");
            tmpdef.append("stop").attr("offset", "60%").attr("stop-color", "#f7fcb9");
            tmpdef.append("stop").attr("offset", "80%").attr("stop-color", "#FFB2E6");
            tmpdef.append("stop").attr("offset", "100%").attr("stop-color", "#f7fcb9");
            tmpdef = _this.defs.append("linearGradient").attr("id","civil-eternity-time");
            tmpdef.append("stop").attr("offset", "0%").attr("stop-color", "#FFB2E6");
            tmpdef.append("stop").attr("offset", "20%").attr("stop-color", "#f7fcb9");
            tmpdef.append("stop").attr("offset", "40%").attr("stop-color", "#AD85FF");
            tmpdef.append("stop").attr("offset", "60%").attr("stop-color", "#FFB2E6");
            tmpdef.append("stop").attr("offset", "80%").attr("stop-color", "#f7fcb9");
            tmpdef.append("stop").attr("offset", "100%").attr("stop-color", "#AD85FF");
            tmpdef = _this.defs.append("linearGradient").attr("id","biological-adoption");
            tmpdef.append("stop").attr("offset", "0%").attr("stop-color", "#FFCD81");
            tmpdef.append("stop").attr("offset", "20%").attr("stop-color", "#A1CB87");
            tmpdef.append("stop").attr("offset", "40%").attr("stop-color", "#FFCD81");
            tmpdef.append("stop").attr("offset", "60%").attr("stop-color", "#A1CB87");
            tmpdef.append("stop").attr("offset", "80%").attr("stop-color", "#FFCD81");
            tmpdef.append("stop").attr("offset", "100%").attr("stop-color", "#A1CB87");

            var g = _this.svg.append("g");

            g.selectAll("path")
            .data(_this.chord.groups)
            .enter().append("path")
            .attr("class", "chordperson")
            .style("fill", function(d) { return _this.fill(d.index); })
            .style("stroke", function(d) { if (_this.isRoot(d.index)) return '#000000'; else return _this.fill(d.index); })
            .attr("d", d3.svg.arc().innerRadius(_this.innerRadius).outerRadius(_this.outerRadius))
            .on("mouseover", fadePerson(.1))
            .on("mouseout", fadePerson(1))
            .text("hi");

            if (_this.useHoverOver) {
                $('.chordperson').tipsy({ 
                    gravity: 'c', 
                    html: true, 
                    offset: 0,
                    hoverlock: true,
                    title: function() {
                        var info = "";
                        var d = this.__data__;
                        if (_this.timepoint == null && _this.people[d.index].divorceDate)
                            info = "<br>Divorced: "+ _this.people[d.index].divorceDate;
                        return _this.people[d.index].name + info; 
                    }
                });
            } else {
                // Put in an element
            }


            _this.svg.append("g")
            .attr("class", "chord")
            .selectAll("path")
            .data(_this.chord.chords)
            .enter().append("path")
            .attr("class", "chordpath")
            .attr("d", d3.svg.chord().radius(_this.innerRadius))
            .style("fill", function(d) { 
                var types = new Array();
                var ret = "none";
                _this.relationships.forEach(function (rel) {
                    if ( (rel.fromId === d.source.index && rel.toId === d.target.index) ||
                        (rel.fromId === d.source.subindex && rel.toId === d.target.subindex) 
                        && types.indexOf(rel.type) == -1) { // don't double up types
                                types.push(rel.type); //ret = _this.fillType(rel.type);
                        }
                });
                if (types.length > 0)
                    ret = _this.fillType(types.join("."));
                    //ret = _this.fillType(types[types.length -1]);
                return ret; })
            .style("stroke", function(d) { 
                var ret = "none";
                _this.relationships.forEach(function (rel) {
                    if ( (rel.fromId === d.source.index && rel.toId === d.target.index) ||
                        (rel.fromId === d.source.subindex && rel.toId === d.target.subindex) )
                        ret = "#000000";
                });
                return ret; })
            .style("opacity", 1)
            .on("mouseover", fadeLink(.1))
            .on("mouseout", fadeLink(1));

            if (_this.useHoverOver) {
                $('.chordpath').tipsy({ 
                    gravity: 'c', 
                    html: true, 
                    offset: 0,
                    hoverlock: false,
                    title: function() {
                        var d = this.__data__;
                        var ret = "";
                        _this.relationships.forEach(function(rel) {
                            if ( (rel.fromId === d.source.index && rel.toId === d.target.index) ||
                                (rel.fromId === d.source.subindex && rel.toId === d.target.subindex) )
                                ret = rel.desc;
                        });
                        return ret; //_this.people[d.index].name; 
                    }
                });
            } else {
                // Put in an element
            }

    }

    // check if is root
    this.isRoot = function (index) {
        var rootB = false;
        _this.relationships.forEach(function(rel) {
            if (( rel.fromId === index || rel.toId === index ) && rel.root === "t")
                rootB = true;
        });
        return rootB; //index >= _this.people.length - 2 && _this.people.length > 2;
    }

    // drawing code below:
    this.drawChord = function(munit) {

        d3.json(_this.json_location(munit), function(data) {

            if (!data || !data.parents || !data.children || !data.relationships)
                return;

            if (data.error) {
                if (_this.innerElement == null)
                    _this.innerElement = d3.select(_this.element).append("div");
                _this.innerElement.html("");
                _this.innerElement.append("h4").text("Error: " + data.error);
                return;
            }

            // recalculate the radii
            _this.innerRadius = Math.min(_this.width, _this.height) * .31,
            _this.outerRadius = _this.innerRadius * 1.3;

            data.parents = data.parents.reverse();
            _this.data = data

            _this.setMatrix(_this.originalTime);

            _this.draw();


        }); // end of json call
    } // end of drawChord


    this.drawTimeSlider = function(element) {

            //stepperdiv.append("span").attr("id","timeText").html("All Time");
            
            // Add the time slider
            var min = 1840, max = 1890;
                var time_slider_scale = d3.scale.linear().domain([min, max]).range([min, max]);
            var time_slider_axis = d3.svg.axis().orient("bottom").ticks(10).scale(time_slider_scale).tickFormat(d3.format(".0f"));
            _this.slider = d3.slider().axis(time_slider_axis).min(min).max(max).on("slide", _this.redraw);
            d3.select(element).append("div").style("margin-top", "30px").attr("id", "sliderDiv").call(_this.slider);
            
            var stepperdiv = d3.select(element).append("div").style("margin-top", "30px");
            stepperdiv.append("button").text("Prev").on("click", _this.goPrevious);
            stepperdiv.append("button").text("All Time").on("click", _this.allTime);
            stepperdiv.append("button").text("Next").on("click", _this.goNext);

    }

    this.goPrevious = function(event, time) {
        _this.slider.value(_this.slider.value() - 1);
        _this.slider.redraw();
        _this.redraw(null, _this.slider.value());
    }
    this.allTime = function(event, time) {
        _this.redraw(null,null);
    }
    this.goNext = function(event, time) {
        _this.slider.value(_this.slider.value() + 1);
        _this.slider.redraw();
        _this.redraw(null, _this.slider.value());
    }

    this.redraw = function(event, time) {

        if (time === null) {
            _this.setMatrix(null);
            d3.select("#timeText").html("All Time");
        } else {
            _this.setMatrix(time + "-01-01");
            d3.select("#timeText").html(time + "-01-01");
        }
        _this.draw();
    }

    // Returns an event handler for fading a given chord group.
    function fadePerson(opacity) {
        return function(g, i) {
            // fade all other persons
            _this.svg.selectAll(".chord path")
            .filter(function(d) { return d.source.index != i && d.target.index != i; })
            .transition()
            .style("opacity", opacity);

            // update hover, if applicable
            if (!_this.useHoverOver) {
                if (opacity == 1)
                    _this.hoverElement.html("&nbsp;<br>&nbsp;");
                else {
                    var info = "<br>&nbsp;";
                    if (_this.timepoint == null && _this.people[i].divorceDate)
                        info = "<br>Divorced: "+ _this.people[i].divorceDate;
                    _this.hoverElement.html(_this.people[i].name + info); 
                }
            }

        };
    }

    // Returns an event handler for fading to one chord
    function fadeLink(opacity) {
        return function(g, i) {
            // fade all other links
            _this.svg.selectAll(".chord path")
            .filter(function(d) { return d.source.index != g.source.index || d.target.index != g.target.index; })
            .transition()
            .style("opacity", opacity);
            
            // update hover, if applicable
            if (!_this.useHoverOver) {
                if (opacity == 1)
                    _this.hoverElement.html("&nbsp;<br>&nbsp;");
                else {
                    var types = new Array();
                    var ret = "";
                    var moreinfo = "";
                    _this.relationships.forEach(function(rel) {
                        if ( (rel.fromId === g.source.index && rel.toId === g.target.index) ||
                            (rel.fromId === g.source.subindex && rel.toId === g.target.subindex) ) {
                            ret = _this.people[rel.fromId].name + " &nbsp;&nbsp;<i>" + rel.desc + "</i>&nbsp;&nbsp; " + _this.people[rel.toId].name;
                            if (rel.desc === "Married To") {
                                moreinfo += "<i>" + rel.type.charAt(0).toUpperCase() + rel.type.slice(1) + " (" 
                                                  + rel.marriageDate + " -- " + rel.divorceDate + ")</i><br>";
                            } else {
                                if (moreinfo === "")
                                    moreinfo = "Type:"
                                moreinfo += " " + rel.type + ",";
                            }
                        }
                    });
                    if (moreinfo.charAt(moreinfo.length - 1) === ",")
                        moreinfo = moreinfo.substring(0,moreinfo.length -1);
                    if (moreinfo.charAt(moreinfo.length - 1) === ">")
                        moreinfo = moreinfo.substring(0,moreinfo.length -4);
                    _this.hoverElement.html(ret + "<br>" + moreinfo); 
                }
            }

        };
    }


} // end ChordDisplay
