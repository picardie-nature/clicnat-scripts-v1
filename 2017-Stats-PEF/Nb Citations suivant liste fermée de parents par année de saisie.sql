-- Arbre taxo à plat recursive specified list of parents

WITH RECURSIVE nodes_cte(id_espece, nom_f, id_espece_parent, depth, path) AS (
 SELECT tn.id_espece, tn.nom_f, tn.id_espece_parent, 1::INT AS depth, tn.id_espece::TEXT AS path 
 FROM especes AS tn 
-- WHERE tn.espece IS NULL
  WHERE tn.id_espece = 6690 --Coléoptères
  OR tn.id_espece = 81 --Insectes
  OR tn.id_espece = 9726 --Poissons : Actinopterygiens
  OR tn.id_espece = 9889 --Crustacés Décapodes
  OR tn.id_espece = 9914 --Annélides, Vers annelés
  
--  WHERE tn.id_espece_parent IS NULL
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
--        u.id_utilisateur,
--        nom,
--        prenom,
--e.reseau_naturaliste,
split_part(e.path,'->',1) as id_espece_racine,
       extract('year'
               FROM o.date_creation) AS Annee_creation,
       count(DISTINCT id_citation) AS nb_citations
FROM utilisateur u,
     observations_observateurs oo,
     observations o,
     citations c,
     arbre_especes e
WHERE 
--Exclude users having at least 1 citation before 2017
--oo.id_utilisateur NOT IN
--    (SELECT DISTINCT oo.id_utilisateur
--     FROM observations o,
--          observations_observateurs oo
--     WHERE o.id_observation=oo.id_observation )
       extract('year' FROM o.date_creation) > 2011  
  AND oo.id_utilisateur=u.id_utilisateur
  AND oo.id_observation=o.id_observation
  AND c.id_observation=o.id_observation
  AND c.id_espece = e.id_espece
GROUP BY
         -- u.id_utilisateur,
         -- nom,
         -- prenom,
         split_part(e.path,'->',1),
         extract('year' FROM o.date_creation)
--HAVING extract('year' FROM date_observation) = '2017'                 
ORDER BY extract('year' FROM o.date_creation)