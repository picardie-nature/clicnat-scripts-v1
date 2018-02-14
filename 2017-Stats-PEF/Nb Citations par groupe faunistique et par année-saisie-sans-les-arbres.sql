
--select nom from reseau;
--
--select * from especes 
--where (classe='A')

-- Arbre taxo à plat recursive on any id
-- bisou

WITH RECURSIVE nodes_cte(id_espece, nom_f, id_espece_parent, depth, path) AS (
 SELECT tn.id_espece, tn.nom_f, tn.id_espece_parent, 1::INT AS depth, tn.id_espece::TEXT AS path 
 FROM especes AS tn 
-- WHERE tn.espece IS NULL
--  WHERE tn.id_espece = 4088
  WHERE tn.id_espece_parent IS NULL
UNION ALL
 SELECT c.id_espece, c.nom_f, c.id_espece_parent, p.depth + 1 AS depth, 
        (p.path || '->' || c.id_espece::TEXT) 
 FROM nodes_cte AS p, especes AS c 
 WHERE c.id_espece_parent = p.id_espece
)
--SELECT * FROM nodes_cte AS n ORDER BY n.id_espece ASC;
, arbre_especes AS
(
SELECT 
max(coalesce(r.nom,'')) as reseau_naturaliste
, e.* 
FROM
nodes_cte e 
LEFT OUTER JOIN reseau_branche_especes re
ON (split_part(e.path,'->',1) = re.id_espece::TEXT 
 or split_part(e.path,'->',2) = re.id_espece::TEXT 
 or split_part(e.path,'->',3) = re.id_espece::TEXT
 or split_part(e.path,'->',4) = re.id_espece::TEXT
 or split_part(e.path,'->',5) = re.id_espece::TEXT
 or split_part(e.path,'->',6) = re.id_espece::TEXT
 or split_part(e.path,'->',7) = re.id_espece::TEXT
 )
LEFT OUTER JOIN reseau r ON re.id_reseau = r.id --AND r.id IN ('cs','ar','sc','li','mm','mt','ml','av','pa','co','ae','sy')
group by e.id_espece, e.nom_f, e.id_espece_parent, e.depth, e.path
order by id_espece
) 
-- suite arbre
-- nb obs par réseau naturaliste
SELECT
e.reseau_naturaliste,
       extract('year'
               FROM o.date_creation) AS annee_creation,
       count(DISTINCT id_citation) AS nb_citations
FROM utilisateur u,
     observations_observateurs oo,
     observations o,
     citations c,
     arbre_especes e
WHERE 
       extract('year'
                   FROM o.date_creation) > 2011  
  AND oo.id_utilisateur=u.id_utilisateur
  AND oo.id_observation=o.id_observation
  AND c.id_observation=o.id_observation
  AND c.id_espece = e.id_espece
GROUP BY
         e.reseau_naturaliste,
         extract('year'
                 FROM o.date_creation)          
ORDER BY extract('year'
                 FROM o.date_creation) DESC, count(DISTINCT id_citation) DESC;
                 
-- 


WITH especes_et_reseaux as (
SELECT 'Syrphes' as reseau, e.* FROM especes e where famille ilike 'syrph%' and classe='I'
UNION ALL
SELECT 'Chiroptères' as reseau, e.* FROM especes e where classe='M' and ordre ilike 'chiropt%'
UNION ALL 
SELECT 'Amphibiens - reptiles' as reseau, e.* FROM especes e where (classe='R' or classe='B')
UNION ALL 
SELECT 'Criquets - sauterelles' as reseau, * FROM especes  where classe='I' and (ordre ilike 'orthopt%' or ordre ilike 'Dermaptera' or especes.id_espece in (96,4184,4648,4646,4647,4649,4616,49,4785,4913,5441,4785,4616,49))
UNION ALL 
SELECT 'Libellules' as reseau, e.* FROM especes e where classe='I' and ordre ilike 'odonat%'
UNION ALL 
SELECT 'Mammifères marins' as reseau, e.* FROM especes e where classe='M' and (ordre ilike 'pinni%' or ordre ilike 'c%tac%')
UNION ALL
SELECT 'Mammifères terrestres' as reseau, e.* FROM especes e where classe='M' and ordre not ilike 'chiro%' and ordre not ilike 'pinni%' and ordre not ilike 'c%tac%'
UNION ALL
SELECT 'Mollusques' as reseau, e.* FROM especes e where classe in ('L','G')
UNION ALL
SELECT 'Oiseaux' as reseau, e.* FROM especes e where classe='O'
UNION ALL
SELECT 'Papillons' as reseau, e.* FROM especes e where classe='I' and ordre ilike 'l%pidopt%'
UNION ALL
SELECT 'Araignées' as reseau, e.* FROM especes e where classe='A'
UNION ALL
SELECT 'Coccinelles' as reseau, * FROM especes  where especes.id_espece in (select id_espece from listes_especes_data where id_liste_espece=21)
UNION ALL
SELECT 'Coléoptères' as reseau, e.* FROM especes e where classe='I' and ordre ilike 'col%opt%'
UNION ALL
SELECT 'Poissons' as reseau, e.* FROM especes e where classe='P'
UNION ALL
SELECT 'Crustacés' as reseau, e.* FROM especes e where classe='C'
UNION ALL
SELECT 'Annélides' as reseau, e.* FROM especes e where classe='N'
UNION ALL
SELECT 'Punaises' as reseau, * FROM especes where especes.id_espece in (select id_espece from listes_especes_data where id_liste_espece=456)
UNION ALL 
SELECT 'Autre insectes' as reseau, * FROM especes  where classe='I' and
					especes.id_espece not in (select id_espece from especes where 
						classe='I' and (ordre ilike 'orthopt%' or ordre ilike 'Dermaptera' or especes.id_espece in (96,4184,4648,4646,4647,4649,4616,49,4785,4913,5441,4785,4616,49))
						or
						(classe='I' and ordre ilike 'odonat%')
						or
						(classe='I' and ordre ilike 'l%pidopt%')
						or
						(classe='I' and ordre ilike 'col%opt%')
						or
						(especes.id_espece in (select id_espece from listes_especes_data where id_liste_espece=21))
						or
						(famille ilike 'syrph%' and classe='I')
						or
						(especes.id_espece in (select id_espece from listes_especes_data where id_liste_espece=456))
					)
order by 2
)
-- nb obs par réseau naturaliste
SELECT
e.reseau,
       extract('year'
               FROM o.date_creation) AS annee_creation,
       count(DISTINCT id_citation) AS nb_citations
FROM utilisateur u,
     observations_observateurs oo,
     observations o,
     citations c,
     especes_et_reseaux e
WHERE 
       extract('year'
                   FROM o.date_creation) > 2011  
  AND oo.id_utilisateur=u.id_utilisateur
  AND oo.id_observation=o.id_observation
  AND c.id_observation=o.id_observation
  AND c.id_espece = e.id_espece
GROUP BY
         e.reseau,
         extract('year'
                 FROM o.date_creation)          
ORDER BY extract('year'
                 FROM o.date_creation) DESC, count(DISTINCT id_citation) DESC;