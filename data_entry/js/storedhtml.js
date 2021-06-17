/**
 * @author Ryan Pope <rcp3by@virginia.edu>
 */

function makeValueSelector(keys, aliases, type) {
  let out = is_isnot_selector;
  out += '<select id="constraint-ID" onchange="updateConstraint(ID)" class="param-' + type + '">';
  for (let i = 0; i < keys.length; i++) {
    out += '<option value="' + keys[i] + '">' + aliases[i] + "</option>";
  }
  out += "</select>";
  return out;
}

const is_isnot_selector = `
<select id="is-isnot-ID" class="is-isnot">
  <option value="is">is</option>
  <option value="isnot">is not</option>
</select>

`;

const date_selector =
  is_isnot_selector +
  `
        <select id="constraint-ID" onchange="updateConstraint(ID)" class="param-date-classifier">
            <option value="before">before</option>
            <option value="on">on</option>
            <option value="after" selected>after</option>
            <option value="known">known</option>
            <option value="unknown">unknown</option>
        </select>
        <input type="date" id="date-ID" value="1856-06-01" class="param-date"/>

`;

const martype_selector =
  is_isnot_selector +
  `
        <select id="constraint-ID" class="param-martype">
            <option value="civil">Civil</option>
            <option value="eternity">Eternity</option>
            <option value="time">Time</option>
            <option value="byu">BYU</option>
            <option value="unknown">unknown</option>
        </select>

`;

const string_selector =
  is_isnot_selector +
  `
        <input type="text" id="constraint-ID" class="param-text" />

`;

const num_selector =
  is_isnot_selector +
  `
        <select id="constraint-ID" onchange="updateConstraint(ID)" class="param-num-classifier">
            <option value="less">less than</option>
            <option value="equal">equal to</option>
            <option value="greater">greater than</option>
        </select>
        <input type="number" id="num-ID" value="1" class="param-num"/>

`;

const office_selector =
  is_isnot_selector +
  `
        <select id="constraint-ID" onchange="updateConstraint(ID)" class="param-office">
          <option value="First Presidency">First Presidency</option>
          <option value="Apostle">Apostle</option>
          <option value="Seventy">Seventy</option>
          <option value="High Priest">High Priest</option>
          <option value="Elder">Elder</option>
          <option value="Teacher">Teacher</option>
          <option value="Priest">Priest</option>
          <option value="Deacon">Deacon</option>
          <option value="Bishop">Bishop</option>
          <option value="Patriarch">Patriarch</option>
          <option value="Council of Fifty">Council of Fifty</option>
          <option value="Relief Society">Relief Society</option>
          <option value="Temple Worker">Temple Worker</option>
          <option value="Midwife">Midwife</option>
          <option value="Female Relief Society of Nauvoo">Female Relief Society of Nauvoo</option>
          <option value="known">known</option>
          <option value="unknown">null/unknown</option>
        </select>

`;

const known_unknown_selector =
  is_isnot_selector +
  `
<select id="constraint-ID" class="param-known-unknown">
    <option value="known">known</option>
    <option value="unknown">unknown</option>
</select>

`;

const person_sort = `
          <option value="Last" selected>Last Name</option>
          <option value="First">First Name</option>
          <option value="BirthDate">Birth Date</option>
          <option value="DeathDate">Death Date</option>
          <option value="Lifespan">Lifespan</option>
          <option value="MarriageCount"># of Spouses</option>
          <option value="TotChildCount">Total Children</option>
          <option value="NatChildCount">Natural Children</option>
          <option value="AdChildCount">Adopted Children</option>

`;

const marriage_sort = `
          <option value="HusbandLast" selected>Husband's Last Name</option>
          <option value="WifeLast">Wife's Last Name</option>
          <option value="HusbandFirst">Husband's First Name</option>
          <option value="WifeFirst">Wife's First Name</option>
          <option value="MarriageDate">Marriage Date</option>
          <option value="HusbandAge">Husband's Age at Marriage</option>
          <option value="WifeAge">Wife's Age at Marriage</option>
          <option value="AgeDiff">Age Difference</option>
`;

const person_DB_restrict = `
          <option value="All" selected>All People</option>
          <option value="AnnointedQuorum">Annointed Quorum</option></select>
`;

const marriage_DB_restrict = `
          <option value="All" selected>All Marriages</option>
`;

const base_adoptions = 
`
<div id="line-ID">
        <select id="restrict-ID" onchange="updateRestrictor(ID)" class="param-column-select">
          <option value="AdoptionDate">Adoption Date</option>
          <option value="AdopteeName">Adoptee Name</option>
          <option value="FatherName">Father Name</option>
          <option value="MotherName">Mother Name</option>
          <option value="BirthMarriageType">Birth Marriage Type</option>
        </select>
        <span id="spec-ID">
          ` +
  date_selector +
  `
        </span>
        <button class="delete-button" onclick="deleteRestrictor(ID)">X</button>
      </div>

`;

const base_marriages =
  `
<div id="line-ID">
        <select id="restrict-ID" onchange="updateRestrictor(ID)" class="param-column-select">
          <option value="MarriageDate">Marriage Date</option>
          <option value="Phase">Phase</option>
          <option value="Type">Marriage Type</option>
          <option value="DivorceDate">Divorce Date</option>
          <option value="CancelledDate">Cancellation Date</option>
          <option value="HusbandFirst">Husband's First Name</option>
          <option value="HusbandLast">Husband's Last Name</option>
          <option value="WifeFirst">Wife's First Name</option>
          <option value="WifeLast">Wife's Last Name</option>
          <option value="HusbandOffice">Husband's Office</option>
          <option value="WifeOffice">Wife's Office</option>
        </select>
        <span id="spec-ID">
          ` +
  date_selector +
  `
        </span>
        <button class="delete-button" onclick="deleteRestrictor(ID)">X</button>
      </div>

`;

const base_people =
  `
<div id="line-ID">
        <select id="restrict-ID" onchange="updateRestrictor(ID)" class="param-column-select">
          <option value="BirthDate">Birth Date</option>
          <option value="DeathDate">Death Date</option>
          <option value="First">First Name</option>
          <option value="Last">Last Name</option>
          <option value="Lifespan">Lifespan</option>
          <option value="Office">Office</option>
          <option value="MarriageCount"># of Spouses</option>
          <option value="NatChildCount"># of Children</option>
        </select>
        <span id="spec-ID">
          ` +
  date_selector +
  `
        </span>
        <button class="delete-button" onclick="deleteRestrictor(ID)">X</button>
      </div>
`;
