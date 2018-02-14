

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
--        u.id_utilisateur,
--        nom,
--        prenom,
e.reseau_naturaliste,
       extract('year'
               FROM date_observation) AS Annee_obs,
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
       extract('year'
                   FROM date_observation) > 2011  
  AND oo.id_utilisateur=u.id_utilisateur
  AND oo.id_observation=o.id_observation
  AND c.id_observation=o.id_observation
  AND c.id_espece = e.id_espece
GROUP BY
         -- u.id_utilisateur,
         -- nom,
         -- prenom,
         e.reseau_naturaliste,
         extract('year'
                 FROM date_observation)
--HAVING extract('year' FROM date_observation) = '2017'                 
ORDER BY extract('year'
                 FROM date_observation) DESC, count(DISTINCT id_citation) DESC;