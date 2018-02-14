SELECT
       coalesce(dep.reference,'vide') as departement_utilisateur
       , extract('year'
               FROM date_observation) AS Annee_obs
--       , c.id_citation
       , u.id_utilisateur
       , u.nom
       , u.prenom
       , count(distinct id_citation) as nb_citations
FROM utilisateur u LEFT OUTER JOIN espace_departement dep ON ST_Intersects(u.the_geom, dep.the_geom)
     JOIN observations_observateurs oo ON oo.id_utilisateur = u.id_utilisateur
     JOIN observations o ON oo.id_observation=o.id_observation
     JOIN citations c ON c.id_observation=o.id_observation
WHERE u.id_utilisateur NOT IN
      (SELECT DISTINCT oo.id_utilisateur
       FROM observations o,
            observations_observateurs oo
       WHERE o.id_observation=oo.id_observation
         -- AND oo.id_observation <= 327522
         AND extract('year'
                     FROM date_observation) < 2017)
group by reference, Annee_obs, u.id_utilisateur, u.nom, u.prenom
order by 1,2 desc;

--SELECT * from utilisateur where id_utilisateur = '5280'