  SELECT
       u.id_utilisateur,
       nom,
       prenom,
--       e.ordre,
--       e.famille,
       extract('year'
               FROM date_observation) AS Annee_obs,
       count(DISTINCT id_citation) AS nb_citations
FROM utilisateur u,
     observations_observateurs oo,
     observations o,
     citations c,
     especes e
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
  AND c.id_espece = e.id_espece
  AND u.diffusion_restreinte = TRUE
GROUP BY
          u.id_utilisateur,
          nom,
          prenom,
--         e.ordre,
--         e.famille,
         extract('year'
                 FROM date_observation)
--HAVING extract('year' FROM date_observation) = '2017'                 
ORDER BY id_utilisateur, extract('year'
                 FROM date_observation) DESC, count(DISTINCT id_citation) DESC;