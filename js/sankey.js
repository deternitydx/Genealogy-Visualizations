d3.sankey = function() {
  var sankey = {},
      nodeWidth = 24,
      nodePadding = 8,
      nodeBreadth = 0,
      size = [1, 1],
      nodes = [],
      links = [],
      numBreadths = 1;

  sankey.nodeWidth = function(_) {
    if (!arguments.length) return nodeWidth;
    nodeWidth = +_;
    return sankey;
  };

  sankey.nodeBreadth = function(_) {
    if (!arguments.length) return nodeBreadth;
    nodeBreadth = +_;
    return sankey;
  };

  sankey.nodePadding = function(_) {
    if (!arguments.length) return nodePadding;
    nodePadding = +_;
    return sankey;
  };

  sankey.nodes = function(_) {
    if (!arguments.length) return nodes;
    nodes = _;
    return sankey;
  };

  sankey.links = function(_) {
    if (!arguments.length) return links;
    links = _;
    return sankey;
  };

  sankey.size = function(_) {
    if (!arguments.length) return size;
    size = _;
    return sankey;
  };

  sankey.layout = function(iterations) {
    nodePadding = Math.max(nodePadding, sankey.nodeWidth());
    computeNodeLinks();
    computeNodeValues();
    computeNodeBreadths();
    if (nodeBreadth == 0) {
        scaleNodeBreadths((size[0] - nodeWidth) / numBreadths);
    } else {
        scaleNodeBreadths(nodeBreadth);
        updateWidth();
    }
    updateHeight();
    computeNodeDepths(iterations);
    computeLinkDepths();
    return sankey;
  };

  sankey.relayout = function() {
    computeLinkDepths();
    return sankey;
  };

  sankey.link = function() {
    var curvature = .5;

    function link(d) {
      var x0 = d.source.x + d.source.dx,
          x1 = d.target.x,
          xi = d3.interpolateNumber(x0, x1),
          x2 = xi(curvature),
          x3 = xi(1 - curvature),
          y0 = d.source.y + d.sy + d.sdy / 2,
          y1 = d.target.y + d.ty + d.tdy / 2;
      return "M" + x0 + "," + y0
           + "C" + x2 + "," + y0
           + " " + x3 + "," + y1
           + " " + x1 + "," + y1;
    }

    link.curvature = function(_) {
      if (!arguments.length) return curvature;
      curvature = +_;
      return link;
    };

    return link;
  };

  // Populate the sourceLinks and targetLinks for each node.
  // Also, if the source and target are not objects, assume they are indices.
  function computeNodeLinks() {
    nodes.forEach(function(node) {
      node.sourceLinks = [];
      node.targetLinks = [];
    });
    links.forEach(function(link) {
      var source = link.source,
          target = link.target;
      if (typeof source === "number") source = link.source = nodes[link.source];
      if (typeof target === "number") target = link.target = nodes[link.target];
      source.sourceLinks.push(link);
      target.targetLinks.push(link);
    });
  }

  // Compute the value (size) of each node by summing the associated links.
  function computeNodeValues() {
    nodes.forEach(function(node) {
      var s = d3.sum(node.sourceLinks, svalue);
      var t = d3.sum(node.targetLinks, tvalue);
      // If all sources or targets have source/target value 1, then collapse them down
      if (s / node.sourceLinks.length == 1)
          s = 1;
      if (t / node.targetLinks.length == 1)
          t = 1;
      node.value = Math.max(s,t);

    });
  }

  // Iteratively assign the breadth (x-position) for each node.
  // Nodes are assigned the maximum breadth of incoming neighbors plus one;
  // nodes with no incoming links are assigned breadth zero, while
  // nodes with no outgoing links are assigned the maximum breadth.
  //
  // Note: node.sourceLinks are the nodes that are descendents (this node is their source)
  //       node.targetLinks are the nodes that are ancestors (this node is their target)
  function computeNodeBreadths() {
    var remainingNodes = nodes,
        nextNodes,
        x = 0;

    while (remainingNodes.length) {
      nextNodes = [];
      remainingNodes.forEach(function(node) {
        node.x = x;
        node.dx = nodeWidth;
        node.sourceLinks.forEach(function(link) {
          nextNodes.push(link.target);
        });
      });
      remainingNodes = nextNodes;
      ++x;
    }

    // full depth is x - 1
    numBreadths = x - 1;

    var changed = true;
    while (changed) {
        changed = false;
        remainingNodes = nodes;
        while (remainingNodes.length) {
            nextNodes = [];
            remainingNodes.forEach(function(node) {
                // maybe we can move it
                if (node.x < numBreadths) {
                    // if it has nodes that count it as a source, then move it forward to just next to it's closest child
                    if (node.sourceLinks.length) {
                        node.x = d3.min(node.sourceLinks, function(d) { return d.target.x; }) - 1;
                        //changed = true;
                    }
                    // if it has no nodes that count it as a source, then we need to look back
                    // Note: edge nodes on the right will not be included here
                    else {
                        var currLevel = node.x;
                        var minNext = currLevel;
                        // If this node is counted as a target
                        if (node.targetLinks.length) {
                            // check each of the sources for the minimum next edge
                            node.targetLinks.forEach( function (link) {
                                if (link.target !== node) {
                                    var minNextTmp = d3.min(link.source.sourceLinks, function(d) { return d.target.x; }) - 1;
                                    if (minNextTmp < minNext)
                                        minNext = minNextTmp;
                                }
                            });
                        }

                        if (minNext > currLevel) {
                            node.x = minNext;
                            //changed = true;
                        }
                    }
                    node.targetLinks.forEach(function(link) {
                        nextNodes.push(link.source);
                    });
                }
            });
            remainingNodes = nextNodes;
        }
    }

    //
    //moveSinksRight(x);
    //moveSourcesRight();
  }

  function moveSourcesRight() {
    var changed = true;
    while (changed) {
        changed = false;
        nodes.forEach(function(node) {
            if (!node.targetLinks.length) {
                node.x = d3.min(node.sourceLinks, function(d) { return d.target.x; }) - 1;
                changed = true;
            }
        });
    }
  }

  function moveSinksRight(x) {
    nodes.forEach(function(node) {
      if (!node.sourceLinks.length) {
        node.x = x - 1;
      }
    });
  }

  function scaleNodeBreadths(kx) {
    nodes.forEach(function(node) {
      node.x *= kx;
    });
  }
  function updateWidth() {
    var width = (numBreadths * nodeBreadth) + nodeWidth;
    if (width > size[0]) size[0] = width;
  }
  function updateHeight() {
    var nodesByBreadth = d3.nest()
        .key(function(d) { return d.x; })
        .sortKeys(d3.ascending)
        .entries(nodes)
        .map(function(d) { return d.values; });
    var height = d3.max(nodesByBreadth, function(nodes) { return (nodes.length) * nodePadding; });
    if (height > size[1]) size[1] = height;
  }

  function computeNodeDepths(iterations) {
    var nodesByBreadth = d3.nest()
        .key(function(d) { return d.x; })
        .sortKeys(d3.ascending)
        .entries(nodes)
        .map(function(d) { return d.values; });

    //
    initializeNodeDepth();
    resolveCollisions();
    for (var alpha = 1; iterations > 0; --iterations) {
      relaxRightToLeft(alpha *= .99);
      resolveCollisions();
      relaxLeftToRight(alpha);
      resolveCollisions();
    }


    function initializeNodeDepth() {
      var ky = d3.min(nodesByBreadth, function(nodes) {
        return (size[1] - (nodes.length - 1) * nodePadding) / d3.sum(nodes, value);
      });

      nodesByBreadth.forEach(function(nodes) {
        nodes.forEach(function(node, i) {
          node.y = i;
          node.dy = node.value * ky;
        });
      });

      // Set the height of each link (link.dy), using link.value
      links.forEach(function(link) {
        link.dy = link.value * ky;
        link.sdy = link.svalue * ky;
        link.tdy = link.tvalue * ky;
      });
    }

    function relaxLeftToRight(alpha) {
      nodesByBreadth.forEach(function(nodes, breadth) {
        nodes.forEach(function(node) {
          if (node.targetLinks.length) {
            var y = d3.sum(node.targetLinks, weightedSource) / d3.sum(node.targetLinks, svalue);
            node.y += (y - center(node)) * alpha;
          }
        });
      });

      function weightedSource(link) {
        return center(link.source) * link.svalue;
      }
    }

    function relaxRightToLeft(alpha) {
      nodesByBreadth.slice().reverse().forEach(function(nodes) {
        nodes.forEach(function(node) {
          if (node.sourceLinks.length) {
            var y = d3.sum(node.sourceLinks, weightedTarget) / d3.sum(node.sourceLinks, tvalue);
            node.y += (y - center(node)) * alpha;
          }
        });
      });

      function weightedTarget(link) {
        return center(link.target) * link.tvalue;
      }
    }

    function resolveCollisions() {
      nodesByBreadth.forEach(function(nodes) {
        var node,
            dy,
            y0 = 0,
            n = nodes.length,
            i;

        // Push any overlapping nodes down.
        nodes.sort(ascendingDepth);
        for (i = 0; i < n; ++i) {
          node = nodes[i];
          dy = y0 - node.y;
          if (dy > 0) node.y += dy;
          y0 = node.y + node.dy + nodePadding;
        }

        // If the bottommost node goes outside the bounds, push it back up.
        dy = y0 - nodePadding - size[1];
        if (dy > 0) {
          y0 = node.y -= dy;

          // Push any overlapping nodes back up.
          for (i = n - 2; i >= 0; --i) {
            node = nodes[i];
            dy = node.y + node.dy + nodePadding - y0;
            if (dy > 0) node.y -= dy;
            y0 = node.y;
          }
        }
      });
    }

    function ascendingDepth(a, b) {
      return a.y - b.y;
    }
  }

  function computeLinkDepths() {
    nodes.forEach(function(node) {
      //node.sourceLinks.sort(ascendingTargetDepth);
      //node.targetLinks.sort(ascendingSourceDepth);
    });
    nodes.forEach(function(node) {
      var sy = 0, ty = 0;
      node.sourceLinks.forEach(function(link) {
        link.sy = sy;
        if (link.svalue != 1) // if they are all 1, then start all from the top
            sy += link.sdy;
      });
      node.targetLinks.forEach(function(link) {
        link.ty = ty;
        if (link.tvalue != 1) // if they are all 1, then start all from the top
            ty += link.tdy;
      });
    });

    function ascendingSourceDepth(a, b) {
      return a.source.y - b.source.y;
    }

    function ascendingTargetDepth(a, b) {
      return a.target.y - b.target.y;
    }
  }

  function center(node) {
    return node.y + node.dy / 2;
  }

  function value(link) {
    return link.value;
  }

  function svalue(link) {
    return link.svalue;
  }

  function tvalue(link) {
    return link.tvalue;
  }

  return sankey;
};
