SELECT reference, annee_creation, count(distinct id_citation) as nb_citations
FROM (
-- espace_point
SELECT
       dep.reference,
       extract('year'
               FROM o.date_creation) AS annee_creation,
       c.id_citation
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
  
UNION ALL
-- espace_line
SELECT
       dep.reference,
       extract('year'
               FROM o.date_creation) AS annee_creation,
       c.id_citation
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
  
UNION ALL
-- espace_polygon
SELECT
       dep.reference,
       extract('year'
               FROM o.date_creation) AS annee_creation,
       c.id_citation
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
  
UNION ALL
-- espace_chiro
SELECT
       dep.reference,
       extract('year'
               FROM o.date_creation) AS annee_creation,
       c.id_citation
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
  
  UNION ALL
-- espace_commune
SELECT
       dep.reference,
       extract('year'
               FROM o.date_creation) AS annee_creation,
       c.id_citation
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
  
  UNION ALL
  -- espace_departement
SELECT
       dep.reference,
       extract('year'
               FROM o.date_creation) AS annee_creation,
       c.id_citation
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
) U
group by reference, annee_creation
order by 1,2 desc;