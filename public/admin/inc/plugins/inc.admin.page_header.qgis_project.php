<script language='Javascript'>
    $('#mapfiles_manager').find('tr.ui-widget').append($('<th />', {text : 'Progetto QGis'}));
    var mapfileManagerTbl = document.getElementById("mapfiles_manager").getElementsByTagName('table')[0];
    var QGisHeader = mapfileManagerTbl.rows[1].insertCell(-1);
    QGisHeader.style.textAlign = 'center';
    QGisHeader.innerHTML = '<a href="#" data-action="refresh_qgis" data-mapset=""><?php echo GCAuthor::t('update') ?></a>';
    <?php
        if(isset($mapsets)) {
            $rowIndex = 2;
            foreach($mapsets as $mapset) {
                echo "var QGisProj_$rowIndex = mapfileManagerTbl.rows[$rowIndex].insertCell(-1);";
                echo "QGisProj_$rowIndex.style.textAlign = 'center';";
                echo "QGisProj_$rowIndex" . '.innerHTML = \'<a data-action="download_qgis" href="'. QGIS_PROJECT_URL .$mapset['mapset_name'].'.qgs" target="_blank">QGis Project</a><a href="#" data-action="refresh_qgis" data-mapset="'.$mapset['mapset_name'].'">'.GCAuthor::t('update').'</a>\';';
                $rowIndex++;
            }
        }
    ?>
</script>
