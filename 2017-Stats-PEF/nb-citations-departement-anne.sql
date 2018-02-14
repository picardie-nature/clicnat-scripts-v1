-- espace_point
SELECT
--        u.id_utilisateur,
--        nom,
--        prenom,
--       e.ordre,
--       e.famille,
       dep.reference,
       extract('year'
               FROM date_observation) AS Annee_obs,
       count(DISTINCT c.id_citation) AS nb_citations
FROM utilisateur u,
     observations_observateurs oo,
     observations o,
     citations c,
--    ,citations_tags ct
     espace_point es
    ,espace_departement dep
--     ,especes e
WHERE 
--Exclude users having at least 1 citation before 2017
--oo.id_utilisateur NOT IN
--    (SELECT DISTINCT oo.id_utilisateur
--     FROM observations o,
--          observations_observateurs oo
--     WHERE o.id_observation=oo.id_observation
--       AND extract('year'
--                   FROM date_observation) < 2017) AND 
      oo.id_utilisateur=u.id_utilisateur
  AND oo.id_observation=o.id_observation
  AND c.id_observation=o.id_observation
--  AND c.id_espece = e.id_espece
--  AND c.id_citation = ct.id_citation
--  AND ct.id_tag = '626'
-- es.departement_id_espace
  AND o.id_espace = es.id_espace
  AND o.espace_table = 'espace_point'
  
  AND ST_Intersects(es.the_geom, dep.the_geom)
GROUP BY
         -- u.id_utilisateur,
         -- nom,
         -- prenom,
--         e.ordre,
--         e.famille,
         dep.reference,
         extract('year'
                 FROM date_observation)
--HAVING extract('year' FROM date_observation) = '2017'                 
ORDER BY extract('year'
                 FROM date_observation) DESC, count(DISTINCT c.id_citation) DESC;