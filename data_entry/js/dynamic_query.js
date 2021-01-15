/**
 * @author Ryan Pope <rcp3by@virginia.edu>
 */

let lineID = 1;
let rtype = "Person";
let rows = [1];
var results = null;
$(document).ready(function () {
  $("select").each(function () {
    $(this).val($(this).find("option[selected]").val());
  });
  lineID = 1;
  $("#param-selector").html("<span id='and-label-" + lineID + "'>where... </span>" + base_people.replace(/ID/g, lineID));
  $("#sort-selector").html(person_sort);
  lineID++;
  $("#form-container").show();

  $("#results-view").append(
    "<thead><tr><th>Full Name</th><th>Birth Date</th><th>Death Date</th><th>Lifespan</th><th>Office(s)</th><th>Spouses</th><th>Marriage Types</th><th>Births/Adopt</th></tr></thead>"
  );

  results = $('#results-view').DataTable(
    {
      "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
    }
  );
});

function setResultType(type) {
  rtype = type;
  if (type == "Marriage") {
    lineID = 1;
    $("#param-selector").html("<span id='and-label-" + lineID + "'>where... </span>" + base_marriages.replace(/ID/g, lineID));
    $("#sort-selector").html(marriage_sort);
    $("#db-restrictor").html(marriage_DB_restrict);
    lineID++;
  }
  if (type == "Person") {
    lineID = 1;
    $("#param-selector").html("<span id='and-label-" + lineID + "'>where... </span>" + base_people.replace(/ID/g, lineID));
    $("#sort-selector").html(person_sort);
    $("#db-restrictor").html(person_DB_restrict);
    lineID++;
  }
}

function deleteRestrictor(r) {
  x = Math.min(...rows);
  $("#line-" + r).remove();
  $("#and-label-" + r).remove();
  rows = rows.filter(function (value) {
    return value != r;
  });
  if (x === r) {
    $("#and-label-" + Math.min(...rows)).html("where... ");
  }
  if(rows.length === 0){
    $("#add-button").html("+");
  }
}

function updateRestrictor(r) {
  switch ($("#restrict-" + r).val()) {
    case "Type":
      $("#spec-" + r).html(martype_selector.replace(/ID/g, r));
      break;

    case "MarriageDate":
    case "DivorceDate":
    case "CancelledDate":
    case "BirthDate":
    case "DeathDate":
      $("#spec-" + r).html(date_selector.replace(/ID/g, r));
      break;
    case "First":
    case "Last":
    case "HusbandFirst":
    case "HusbandLast":
    case "WifeFirst":
    case "WifeLast":
      $("#spec-" + r).html(string_selector.replace(/ID/g, r));
      break;
    case "Lifespan":
      $("#spec-" + r).html(known_unknown_selector.replace(/ID/g, r));
      break;
    case "MarriageCount":
    case "NatChildCount":
    case "Phase":
      $("#spec-" + r).html(num_selector.replace(/ID/g, r));
      break;
    case "Office":
      $("#spec-" + r).html(office_selector.replace(/ID/g, r));
      break;
  }
}
function updateConstraint(r) {
  switch ($("#constraint-" + r).val()) {
    case "known":
    case "unknown":
      $("#date-" + r).hide();
      break;

    case "on":
    case "before":
    case "after":
      $("#date-" + r).show();
      break;
  }
}
function addNewConstraint() {
  $("#add-button").html("+ and");
  rows.push(lineID);
  let and_where = "";
  if (rows.length === 1) {
    and_where = "where... ";
  } else {
    and_where = "and ";
  }
  if (rtype === "Marriage") {
    $("#param-selector").append("<span id='and-label-" + lineID + "'>" + and_where + "</span>" + base_marriages.replace(/ID/g, lineID));
    lineID++;
  }
  if (rtype === "Person") {
    $("#param-selector").append("<span id='and-label-" + lineID + "'>" + and_where + "</span>" + base_people.replace(/ID/g, lineID));
    lineID++;
  }
}

function getResult() {

  $("#results-view_wrapper").remove();
  $("#stats-view").after("<table id='results-view'></table>");

  var start = Date.now();

  $("#results-view_wrapper").hide();
  $("#stats-view").html("");
  $("#stats-view").hide();
  $("#result-count").hide();
  $("#processing-info").html("Fetching query results...");
  $("#processing-sub").html("Queries with more results may take a while to appear.");
  $("#processing-sub").show();

  let columns = $(".param-column-select")
    .map(function () {
      return $(this).val();
    })
    .toArray();

  let isIsNot = $(".is-isnot")
    .map(function () {
      return $(this).val();
    })
    .toArray();

  let dates = $(".param-date")
    .map(function () {
      return $(this).val();
    })
    .toArray();

  let dateClassifiers = $(".param-date-classifier")
    .map(function () {
      return $(this).val();
    })
    .toArray();

  let martypes = $(".param-martype")
    .map(function () {
      return $(this).val();
    })
    .toArray();

  let texts = $(".param-text")
    .map(function () {
      return $(this).val();
    })
    .toArray();

  let knunk = $(".param-known-unknown")
    .map(function () {
      return $(this).val();
    })
    .toArray();

  let nums = $(".param-num")
    .map(function () {
      return parseInt($(this).val());
    })
    .toArray();

  let numClassifiers = $(".param-num-classifier")
    .map(function () {
      return $(this).val();
    })
    .toArray();

  let offices = $(".param-office")
    .map(function () {
      return $(this).val();
    })
    .toArray();

  let req = $.post(
    "custom_query.php",
    {
      res: $("#result-classifier").val(),
      sort: $("#sort-selector").val(),
      dir: $("#direction-selector").val(),
      mt: JSON.stringify(martypes),
      dt: JSON.stringify(dates),
      dtcls: JSON.stringify(dateClassifiers),
      cols: JSON.stringify(columns),
      txt: JSON.stringify(texts),
      knu: JSON.stringify(knunk),
      num: JSON.stringify(nums),
      numcls: JSON.stringify(numClassifiers),
      off: JSON.stringify(offices),
      lim: $("#limit-selector").val(),
      restrict: $("#db-restrictor").val(),
      isisnot: JSON.stringify(isIsNot),
    },
    function (data) {
      console.log(data);
      data = data.replace(/null/g, "\"<span class='null-result'>null</span>\"");
      d = JSON.parse(data);
      if (typeof d != "object") {
        $("#processing-info").html("Your query returned no results.");
        var end = Date.now();
        $("#processing-sub").html("(" + (end - start) / 1000 + " seconds)");
      } else {
        $("#processing-sub").hide();
        $("#download-info").show();
        //console.log(d.length-1);
        $("#processing-info").html("");
        if (rtype == "Person") {
          $("#results-view").append(
            "<thead><tr><th>Full Name</th><th>Birth Date</th><th>Death Date</th><th>Lifespan</th><th>Office(s)</th><th>Spouses</th><th>Marriage Types</th><th>Births/Adopt</th></tr></thead>"
          );
          stats = d[0][0];
          $("#result-count").html(stats.ResultCount + " results (" + (d.length - 1) + " shown)");
          $("#result-count").show();
          //console.log(stats);
          $("#stats-view").append("<tr><td></td><th>Lifespan</th><th>Spouses</th><th>Children</th></tr>");

          $("#stats-view").append(
            "<tr><th>Minimum</th><td>" +
              stats.MinLifespan +
              " (" +
              parseFloat(stats.MinLifespanDec).toFixed(2) +
              " years)</td><td>" +
              stats.MinMarriage +
              "</td><td>" +
              stats.MinNatChild +
              "</td></tr>"
          );
          avgLife = stats.AvgLifespan;
          if (avgLife.split(" ").length >= 3) {
            avgLife = avgLife.substring(0, avgLife.lastIndexOf(" "));
          }
          avgLifeDec = parseFloat(stats.AvgLifespanDec).toFixed(2);
          avgMarriage = parseFloat(stats.AvgMarriage).toFixed(2);
          avgNChild = parseFloat(stats.AvgNatChild).toFixed(2);

          $("#stats-view").append(
            "<tr><th>Average</th><td>" + avgLife + " (" + avgLifeDec + " years)</td><td>" + avgMarriage + "</td><td>" + avgNChild + "</td></tr>"
          );
          $("#stats-view").append(
            "<tr><th>Maximum</th><td>" +
              stats.MaxLifespan +
              " (" +
              parseFloat(stats.MaxLifespanDec).toFixed(2) +
              " years)</td><td>" +
              stats.MaxMarriage +
              "</td><td>" +
              stats.MaxNatChild +
              "</td></tr>"
          );
          $("#stats-view").show();
          let tableout = "<tbody>";
          d.slice(1).forEach(function (el){
            tableout += 
              "<tr><td><a href=http://nauvoo.iath.virginia.edu/viz/person.php?id=" +
                el.ID +
                ">" +
                el.FullName.replace(/\s+/g, " ") +
                "</a></td><td>" +
                el.BirthDate +
                "</td><td>" +
                el.DeathDate +
                "</td><td>" +
                el.Lifespan.replace(/years|year/g, "y.")
                  .replace(/mons|mon/g, "mo.")
                  .replace(/days|day/g, "d.") +
                "</td><td>" +
                el.Office +
                "</td><td>" +
                el.MarriageCount +
                "</td><td>" +
                el.MarriageTypes +
                "</td><td>" +
                el.NatChildCount +
                "/" +
                el.AdChildCount +
                "</td></tr>";
            
          });
            $("#results-view").append(tableout + "</tbody>");
        } else if (rtype == "Marriage") {
          $("#results-view").append(
            "<thead><tr><th>D/C?</th><th>Plural?</th><th>Phase</th><th>Marriage Date</th><th>Husband's Name</th><th>Husband's Age at Marriage</th><th>Wife's Name</th><th>Wife's Age at Marriage</th><th>Wife's Age at First Birth</th><th>Marriage Type</th><th>Age Difference</th></tr></thead>"
          );
          stats = d[0][0];
          //console.log(stats.ResultCount);
          let tableout = "<tbody>";
          d.slice(1).forEach(function (el) {
            tableout += "<tr>";
            for (const property in el) {
              // console.log(property, el[property]);
              switch (property) {
                case "WifeID":
                case "HusbandID":
                case "WifeFirst":
                case "WifeLast":
                case "HusbandFirst":
                case "HusbandLast":
                case "DivorceDate":
                case "CancelledDate":
                case "WifeDeath":
                case "HusbandDeath":
                case "MarriageID":
                case "ResultCount":
                  break; //ignore these columns, they won't appear in the table

                case "HusbandName":
                  if (el.HusbandID == "<span class='null-result'>null</span>") {
                    tableout += "<td>" + el.HusbandID + "</td>";
                  } else {
                    tableout += "<td><a href=http://nauvoo.iath.virginia.edu/viz/person.php?id=" + el.HusbandID + ">" + el[property] + "</a></td>";
                  }
                  break;
                case "WifeName":
                  if (el.WifeID == "<span class='null-result'>null</span>") {
                    tableout += "<td>" + el.WifeID + "</td>";
                  } else {
                    tableout += "<td><a href=http://nauvoo.iath.virginia.edu/viz/person.php?id=" + el.WifeID + ">" + el[property] + "</a></td>";
                  }
                  break;
                case "HusbandAge":
                case "WifeAge":
                case "AgeDiff":
                case "WifeFirstBirthAge":
                  let l = el[property].replace(/years|year/g, "y.").replace(/mons|mon/g, "mo.").replace(/days|day/g, "d.");
                  tableout += "<td>" + l + "</td>";
                  break;
                default:
                  tableout += "<td>" + el[property] + "</td>";
              }
            }
            tableout +=  "</tr>";
          });
            $("#results-view").append(tableout + "</tbody>");
          
        }
        var end = Date.now();
        results = $('#results-view').DataTable(
          {
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            dom: 'Blfrtip',
            buttons: [
              'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            "order": [[ 3, "desc" ]]
          }
        );
        // results.buttons().container().insertBefore( '#results-view_filter' );
        $("#results-view_wrapper").show();
        $("#results-view_info").append( "<br/><span id='return-speed'>("+ (end-start) / 1000 + " sec.)</span>" );
      }
    }
  );
}
