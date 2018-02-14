SELECT u.id_utilisateur,
       nom,
       prenom,
       extract('year'
               FROM date_observation) AS annee_obs,
       count(DISTINCT id_citation)
FROM utilisateur u,
     observations_observateurs oo,
     observations o,
     citations c
WHERE oo.id_utilisateur=u.id_utilisateur
  AND oo.id_observation=o.id_observation
  AND c.id_observation=o.id_observation
  AND extract('year'
              FROM date_observation) BETWEEN 2000 AND 2017
GROUP BY u.id_utilisateur,
         nom,
         prenom,
         extract('year'
                 FROM date_observation)
ORDER BY u.id_utilisateur,
         extract('year'
                 FROM date_observation),
         count(DISTINCT id_citation) DESC;
