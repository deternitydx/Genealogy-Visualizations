-- Display list of (A, C, set of B)s for which
-- (Marriage between A and B for time or eternity) and
-- (Marriage between A and C, civil) and
-- not (Marriage between A and C for time or eternity) and
-- //not (C’s death before A+B’s sealing)
-- C's death after A+B's sealing

\copy 
(select a."ID" as "HusbandID", an."Last" as "HusbandLast",
an."First" as "HusbandFirst", 

c."ID" as "WifeCID", cn."Last" as "WifeCLast",
cn."First" as "WifeCFirst", c."DeathDate" as "WifeCDeath",

b."ID" as "WifeBID", bn."Last" as "WifeBLast",
bn."First" as "WifeBFirst", b."DeathDate" as "WifeBDeath",

ab."Type" as "ABType", ab."MarriageDate" as "ABMarriageDate", 
ac."Type" as "ACType", ac."MarriageDate" as "ACMarriageDate"

from public."Person" a, public."Person" b, public."Person" c, 
    public."Name" an, public."Name" bn, public."Name" cn, 
    (select m."Type", m."MarriageDate", h."PersonID" as "HusbandID",
       w."PersonID" as "WifeID" from public."Marriage" m, public."PersonMarriage" h,
       public."PersonMarriage" w
       where (w."MarriageID" = m."ID" and w."Role" = 'Wife') and
            (h."MarriageID" = m."ID" and h."Role" = 'Husband') and
            (m."Type" = 'time' OR m."Type" = 'eternity') 
    ) ab,
    (select m."Type", m."MarriageDate", h."PersonID" as "HusbandID",
       w."PersonID" as "WifeID" from public."Marriage" m, public."PersonMarriage" h,
       public."PersonMarriage" w
       where (w."MarriageID" = m."ID" and w."Role" = 'Wife') and
            (h."MarriageID" = m."ID" and h."Role" = 'Husband') and
            m."Type" <> 'time' and m."Type" <> 'eternity'
            and m."Type" = 'civil'
    ) ac

where (a."ID" = an."PersonID" and an."Type" = 'authoritative')
and (b."ID" = bn."PersonID" and bn."Type" = 'authoritative')
and (c."ID" = cn."PersonID" and cn."Type" = 'authoritative')
and ab."HusbandID" = a."ID" and ab."WifeID" = b."ID"
and ab."HusbandID" = a."ID" and ac."WifeID" = c."ID"
and ab."MarriageDate" is not null and c."DeathDate" is not null
and c."DeathDate" > ab."MarriageDate"

order by a."ID" asc) TO 'big_query.csv' WITH FORMAT 'csv' DELIMITER ',';
