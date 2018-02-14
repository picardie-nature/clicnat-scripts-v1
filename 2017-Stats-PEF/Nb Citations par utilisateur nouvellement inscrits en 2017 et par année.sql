SELECT reference as departement, Annee_obs, id_utilisateur, nom, prenom,count(distinct id_citation) as nb_citations
FROM (
-- espace_point
SELECT
       dep.reference,
       extract('year'
               FROM date_observation) AS Annee_obs,
       c.id_citation
       , u.id_utilisateur, u.nom, u.prenom
FROM utilisateur u,
     observations_observateurs oo,
     observations o,
     citations c,
     espace_point es
    ,espace_departement dep
WHERE
      oo.id_utilisateur=u.id_utilisateur
  AND oo.id_observation=o.id_observation
  AND c.id_observation=o.id_observation
  AND o.id_espace = es.id_espace
  AND o.espace_table = 'espace_point'
  AND ST_Intersects(es.the_geom, dep.the_geom)
  AND oo.id_utilisateur NOT IN
      (SELECT DISTINCT oo.id_utilisateur
       FROM observations o,
            observations_observateurs oo
       WHERE o.id_observation=oo.id_observation
         -- AND oo.id_observation <= 327522
         AND extract('year'
                     FROM date_observation) < 2017)

UNION ALL
-- espace_line
SELECT
       dep.reference,
       extract('year'
               FROM date_observation) AS Annee_obs,
       c.id_citation
       , u.id_utilisateur, u.nom, u.prenom
FROM utilisateur u,
     observations_observateurs oo,
     observations o,
     citations c,
     espace_line es
    ,espace_departement dep
WHERE
      oo.id_utilisateur=u.id_utilisateur
  AND oo.id_observation=o.id_observation
  AND c.id_observation=o.id_observation
  AND o.id_espace = es.id_espace
  AND o.espace_table = 'espace_line'
  AND ST_Intersects(es.the_geom, dep.the_geom)
  AND oo.id_utilisateur NOT IN
      (SELECT DISTINCT oo.id_utilisateur
       FROM observations o,
            observations_observateurs oo
       WHERE o.id_observation=oo.id_observation
         -- AND oo.id_observation <= 327522
         AND extract('year'
                     FROM date_observation) < 2017)

UNION ALL
-- espace_polygon
SELECT
       dep.reference,
       extract('year'
               FROM date_observation) AS Annee_obs,
       c.id_citation
       , u.id_utilisateur, u.nom, u.prenom
FROM utilisateur u,
     observations_observateurs oo,
     observations o,
     citations c,
     espace_polygon es
    ,espace_departement dep
WHERE
      oo.id_utilisateur=u.id_utilisateur
  AND oo.id_observation=o.id_observation
  AND c.id_observation=o.id_observation
  AND o.id_espace = es.id_espace
  AND o.espace_table = 'espace_polygon'
  AND ST_Intersects(es.the_geom, dep.the_geom)
  AND oo.id_utilisateur NOT IN
      (SELECT DISTINCT oo.id_utilisateur
       FROM observations o,
            observations_observateurs oo
       WHERE o.id_observation=oo.id_observation
         -- AND oo.id_observation <= 327522
         AND extract('year'
                     FROM date_observation) < 2017)

UNION ALL
-- espace_chiro
SELECT
       dep.reference,
       extract('year'
               FROM date_observation) AS Annee_obs,
       c.id_citation
       , u.id_utilisateur, u.nom, u.prenom
FROM utilisateur u,
     observations_observateurs oo,
     observations o,
     citations c,
     espace_chiro es
    ,espace_departement dep
WHERE
      oo.id_utilisateur=u.id_utilisateur
  AND oo.id_observation=o.id_observation
  AND c.id_observation=o.id_observation
  AND o.id_espace = es.id_espace
  AND o.espace_table = 'espace_chiro'
  AND ST_Intersects(es.the_geom, dep.the_geom)
  AND oo.id_utilisateur NOT IN
      (SELECT DISTINCT oo.id_utilisateur
       FROM observations o,
            observations_observateurs oo
       WHERE o.id_observation=oo.id_observation
         -- AND oo.id_observation <= 327522
         AND extract('year'
                     FROM date_observation) < 2017)

  UNION ALL
-- espace_commune
SELECT
       dep.reference,
       extract('year'
               FROM date_observation) AS Annee_obs,
       c.id_citation
       , u.id_utilisateur, u.nom, u.prenom
FROM utilisateur u,
     observations_observateurs oo,
     observations o,
     citations c,
     espace_commune es
    ,espace_departement dep
WHERE
      oo.id_utilisateur=u.id_utilisateur
  AND oo.id_observation=o.id_observation
  AND c.id_observation=o.id_observation
  AND o.id_espace = es.id_espace
  AND o.espace_table = 'espace_commune'
  AND ST_Intersects(es.the_geom, dep.the_geom)
  AND oo.id_utilisateur NOT IN
      (SELECT DISTINCT oo.id_utilisateur
       FROM observations o,
            observations_observateurs oo
       WHERE o.id_observation=oo.id_observation
         -- AND oo.id_observation <= 327522
         AND extract('year'
                     FROM date_observation) < 2017)

  UNION ALL
  -- espace_departement
SELECT
       dep.reference,
       extract('year'
               FROM date_observation) AS Annee_obs,
       c.id_citation
       , u.id_utilisateur, u.nom, u.prenom
FROM utilisateur u,
     observations_observateurs oo,
     observations o,
     citations c
    ,espace_departement dep
WHERE
      oo.id_utilisateur=u.id_utilisateur
  AND oo.id_observation=o.id_observation
  AND c.id_observation=o.id_observation
  AND o.id_espace = dep.id_espace
  AND o.espace_table = 'espace_departement'
  AND oo.id_utilisateur NOT IN
      (SELECT DISTINCT oo.id_utilisateur
       FROM observations o,
            observations_observateurs oo
       WHERE o.id_observation=oo.id_observation
         -- AND oo.id_observation <= 327522
         AND extract('year'
                     FROM date_observation) < 2017)
) U
group by reference, Annee_obs, id_utilisateur, nom, prenom
order by 1,2 desc;
