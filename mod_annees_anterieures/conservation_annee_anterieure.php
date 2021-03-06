<?php
/*
 *
 * Copyright 2001, 2016 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun
 *
 * This file is part of GEPI.
 *
 * GEPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GEPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GEPI; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

// Initialisations files
require_once("../lib/initialisations.inc.php");
include("../lib/initialisationsPropel.inc.php");
require_once("./fonctions_annees_anterieures.inc.php");

// Resume session
$resultat_session = $session_gepi->security_check();
if ($resultat_session == 'c') {
    header("Location: ../utilisateurs/mon_compte.php?change_mdp=yes");
    die();
} else if ($resultat_session == '0') {
    header("Location: ../logout.php?auto=1");
    die();};

// INSERT INTO droits VALUES ('/mod_annees_anterieures/conservation_annee_anterieure.php', 'V', 'F', 'F', 'F', 'F', 'F', 'F', 'Conservation des données antérieures', '');
if (!checkAccess()) {
    header("Location: ../logout.php?auto=1");
    die();
}



/*
$prof=isset($_POST['prof']) ? $_POST['prof'] : NULL;
$page=isset($_POST['page']) ? $_POST['page'] : NULL;
$enregistrer=isset($_POST['enregistrer']) ? $_POST['enregistrer'] : NULL;
*/

$id_classe=isset($_POST['id_classe']) ? $_POST['id_classe'] : NULL;
$deja_traitee_id_classe=isset($_POST['deja_traitee_id_classe']) ? $_POST['deja_traitee_id_classe'] : NULL;
$annee_scolaire=isset($_POST['annee_scolaire']) ? $_POST['annee_scolaire'] : NULL;
$confirmer=isset($_POST['confirmer']) ? $_POST['confirmer'] : NULL;

$generer_fichier_sql=isset($_POST['generer_fichier_sql']) ? $_POST['generer_fichier_sql'] : "";

// Si le module n'est pas activé...
if($gepiSettings['active_annees_anterieures'] !="y"){
	// A DEGAGER
	// A VOIR: Comment enregistrer une tentative d'accès illicite?

	header("Location: ../logout.php?auto=1");
	die();
}

$msg="";
/*
if(isset($enregistrer)){

	if($msg==""){
		$msg="Enregistrement réussi.";
	}

	unset($page);
}
*/

// Suppression des données archivées pour une année donnée.
if (isset($_GET['action']) and ($_GET['action']=="supp_annee")) {
	check_token();

	$sql="DELETE FROM archivage_disciplines WHERE annee='".$_GET["annee_supp"]."';";
	$res_suppr1=mysqli_query($GLOBALS["mysqli"], $sql);

	// Maintenant, on regarde si l'année est encore utilisée dans archivage_types_aid
	// Sinon, on supprime les entrées correspondantes à l'année dans archivage_eleves2 car elles ne servent plus à rien.
	$test = sql_query1("select count(annee) from archivage_types_aid where annee='".$_GET['annee_supp']."'");
	if ($test == 0) {
		$sql="DELETE FROM archivage_eleves2 WHERE annee='".$_GET["annee_supp"]."';";
		$res_suppr2=mysqli_query($GLOBALS["mysqli"], $sql);
	} else {
		$res_suppr2 = 1;
	}

	$sql="DELETE FROM archivage_ects WHERE annee='".$_GET["annee_supp"]."';";
	$res_suppr3=mysqli_query($GLOBALS["mysqli"], $sql);

	// Maintenant, il faut supprimer les données élèves qui ne servent plus à rien
	suppression_donnees_eleves_inutiles();

	if (($res_suppr1) and ($res_suppr2) and ($res_suppr3)) {
		$msg = "La suppression des données a été correctement effectuée.";
	} else {
		$msg = "Un ou plusieurs problèmes ont été rencontrés lors de la suppression.";
	}

}

if(isset($_GET['chgt_annee'])) {$_SESSION['chgt_annee']="y";}

$themessage  = 'Etes-vous sûr de vouloir supprimer toutes les données concerant cette année ?';

//**************** EN-TETE *****************
$titre_page = "Conservation des données antérieures (autres que AID)";
require_once("../lib/header.inc.php");
//**************** FIN EN-TETE *****************

echo "<form enctype=\"multipart/form-data\" name= \"formulaire\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">\n";

if(!isset($annee_scolaire)){
	echo "<div class='norme'><p class=bold><a href='";
	if(isset($_SESSION['chgt_annee'])) {
		echo "../gestion/changement_d_annee.php";
	}
	else {
		echo "./index.php";
	}
	echo "'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a> | \n";
	echo "</p></div>\n";

	$sql="SELECT DISTINCT annee FROM archivage_disciplines ORDER BY annee;";
	$res_annee=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($res_annee)==0){
		echo "<p>Concernant les données autres que les AIDs, aucune année n'est encore sauvegardée.</p>\n";
	}
	else{
		echo "<p>Voici la liste des années sauvegardées:</p>\n";
		echo "<ul>\n";
		while($lig_annee=mysqli_fetch_object($res_annee)){
			$annee_scolaire=$lig_annee->annee;
			echo "<li><b>Année $annee_scolaire (<a href='".$_SERVER['PHP_SELF']."?action=supp_annee&amp;annee_supp=".$annee_scolaire.add_token_in_url()."'   onclick=\"return confirm_abandon (this, 'yes', '$themessage')\">Supprimer toutes les données archivées pour cette année</a>) :<br /></b> ";
			$sql="SELECT DISTINCT classe FROM archivage_disciplines WHERE annee='$annee_scolaire' ORDER BY classe;";
			$res_classes=mysqli_query($GLOBALS["mysqli"], $sql);
			if(mysqli_num_rows($res_classes)==0){
				echo "Aucune classe???";
			}
			else{
				$lig_classe=mysqli_fetch_object($res_classes);
				echo $lig_classe->classe;

				while($lig_classe=mysqli_fetch_object($res_classes)){
					echo ", ".$lig_classe->classe;
				}
			}
			echo "</li>\n";
		}
		echo "</ul>\n";
		echo "<p><br /></p>\n";

	}
	echo "<p>Sous quel nom d'année voulez-vous sauvegarder l'année?</p>\n";
	$default_annee=getSettingValue('gepiYear');

	if($default_annee==""){
		$instant=getdate();
		$annee=$instant['year'];
		$mois=$instant['mon'];

		$annee2=$annee+1;
		$default_annee=$annee."-".$annee2;
	}

	echo "<p>Année&nbsp;: <input type='text' name='annee_scolaire' value='$default_annee' /></p>\n";
	echo "<p style='text-indent:-2em; margin-left:2em;'><input type='checkbox' name='generer_fichier_sql' id='generer_fichier_sql' value='".strftime("%Y%m%d%H%M%S")."' onchange=\"modif_affichage_restrictions_fichier_sql()\" /><label for='generer_fichier_sql'> Générer un fichier SQL des enregistrements.<br />(<em>utile si vous faites l'archivage sur une autre machine que le GEPI en production&nbsp;: vous pourrez restaurer ce fichier sur le Gepi en production pour insérer les enregistrements de l'année archivée ici</em>)</label><br />Le fichier sera généré dans <a href='../gestion/accueil_sauve.php'>Gestion générale/Sauvegarde et restauration</a>.</p>
<div id='restrictions_fichier_sql'>
	<p style='text-indent:-6em; margin-left:6em;color:red;'>Limitation&nbsp;: Pour le moment, l'enregistrement dans un fichier SQL ne prend pas en compte les crédits ECTS.<br />Si vous utilisez le module ECTS, le fichier SQL généré ne sera pas complet.<br />Les AID ne sont pas non plus gérés pour le moment.</p>
	<p style='color:red;'>Par ailleurs le fichier généré n'est pas compressé.<br />Il peut facilement occuper 20Mo ou plus.<br />Cela peut être un problème si votre hébergement ne permet pas un tel stockage.</p>
</div>

<script type='text/javascript'>
	function modif_affichage_restrictions_fichier_sql() {
		if(document.getElementById('restrictions_fichier_sql')) {
			if(document.getElementById('generer_fichier_sql').checked==true) {
				document.getElementById('restrictions_fichier_sql').style.display='';
			}
			else {
				document.getElementById('restrictions_fichier_sql').style.display='none';
			}
		}
	}

	modif_affichage_restrictions_fichier_sql();
</script>\n";

	echo "<center><input type=\"submit\" name='ok' value=\"Valider\" style=\"font-variant: small-caps;\" /></center>\n";

	$sql="SELECT DISTINCT e.* FROM eleves e,j_eleves_classes jec WHERE jec.login=e.login AND e.mef_code='';";
	$test=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($test)>0){
		echo "<p style='color:red; margin-top:1em; margin-left:7.5em; text-indent:-7.5em;'><strong>ATTENTION&nbsp;:</strong> ".mysqli_num_rows($test)." élève(s) ont leur CODE_MEF non renseigné.<br />Cela posera problème dans le cas où vous souhaiteriez faire remonter les données dans le <strong>Livret Scolaire Lycée</strong> ou dans le <strong>Livret Scolaire Collège</strong> <em>(LSUN)</em>.<br />Il est recommandé de procéder à l'association élève/MEF avant d'archiver l'année.<br /><a href='../mef/associer_eleve_mef.php'>Associer élèves et MEF</a></p>";
	}

	$sql="SELECT DISTINCT m.matiere FROM matieres m, j_groupes_matieres jgm WHERE jgm.id_matiere=m.matiere AND m.code_matiere='';";
	$test=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($test)>0){
		echo "<p style='color:red; margin-top:1em; margin-left:7.5em; text-indent:-7.5em;'><strong>ATTENTION&nbsp;:</strong> ".mysqli_num_rows($test)." matière(s) ont leur CODE_MATIERE non renseigné.<br />Cela posera problème dans le cas où vous souhaiteriez faire remonter les données dans le <strong>Livret Scolaire Lycée</strong> ou dans le <strong>Livret Scolaire Collège</strong> <em>(LSUN)</em>.<br />Il est recommandé de procéder à l'association matière/CODE_MATIERE avant d'archiver l'année.<br /><a href='../matieres/index.php'>Associer matières et CODE_MATIERE</a><br />Si nécessaire, vous pouvez procéder à un <a href='../gestion/admin_nomenclatures.php'>import des nomenclatures</a> pour disposer des codes matières officiels.</p>";
	}

	$sql="SELECT DISTINCT u.login FROM utilisateurs u, j_groupes_professeurs jgp WHERE jgp.login=u.login AND u.statut='professeur' AND numind='';";
	$test=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($test)>0){
		echo "<p style='color:red; margin-top:1em; margin-left:7.5em; text-indent:-7.5em;'><strong>ATTENTION&nbsp;:</strong> ".mysqli_num_rows($test)." professeur(s) ont leur NUMIND <em>(identifiant STS)</em> non renseigné.<br />Cela posera problème dans le cas où vous souhaiteriez faire remonter les données dans le <strong>Livret Scolaire Lycée</strong> ou dans le <strong>Livret Scolaire Collège</strong> <em>(LSUN)</em>.<br />Il est recommandé de procéder à l'association professeur/NUMIND avant d'archiver l'année.<br /><a href='recuperation_donnees_manquantes.php'>Associer professeurs et NUMIND</a>.</p>";
	}

	$sql="SELECT DISTINCT u.login FROM utilisateurs u, j_groupes_professeurs jgp WHERE jgp.login=u.login AND u.statut='professeur' AND type='';";
	$test=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($test)>0){
		echo "<p style='color:red; margin-top:1em; margin-left:7.5em; text-indent:-7.5em;'><strong>ATTENTION&nbsp;:</strong> ".mysqli_num_rows($test)." professeur(s) ont leur TYPE <em>(\"Emploi Poste Personnel\" ou \"Local\")</em> non renseigné.<br />Cela posera problème dans le cas où vous souhaiteriez faire remonter les données dans le <strong>Livret Scolaire Lycée</strong> ou dans le <strong>Livret Scolaire Collège</strong> <em>(LSUN)</em>.<br />Il est recommandé de procéder à l'association professeur/TYPE avant d'archiver l'année.<br /><a href='recuperation_donnees_manquantes.php'>Associer professeurs et TYPE</a>.</p>";
	}
}
else {
	//==================================
	$fichier_sql="";
	if($generer_fichier_sql!="") {
		$fichier_sql="_archivage_annee_".remplace_accents($annee_scolaire,"all")."_realise_le_".preg_replace("/[^0-9]/","",$generer_fichier_sql).".sql";
	}

	echo "<input type='hidden' name='generer_fichier_sql' value='$generer_fichier_sql' />\n";
	//==================================

	echo "<div class='norme'><p class=bold><a href='";
	if(isset($_SESSION['chgt_annee'])) {
		echo "../gestion/changement_d_annee.php";
	}
	else {
		echo "./index.php";
	}
	echo "'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a> | \n";

	$sql="SELECT DISTINCT classe FROM archivage_disciplines WHERE annee='$annee_scolaire';";
	$res_test=mysqli_query($GLOBALS["mysqli"], $sql);

	if(mysqli_num_rows($res_test)>0){
		if(!isset($confirmer)){
			echo "</p></div>\n";

			$lig_classe=mysqli_fetch_object($res_test);
			$chaine_classes=$lig_classe->classe;

			if(mysqli_num_rows($res_test)>1){
				while($lig_classe=mysqli_fetch_object($res_test)){
					$chaine_classes.=", ".$lig_classe->classe;
				}

				echo "<p>Des données ont déjà été sauvegardées pour l'année $annee_scolaire (<i>classes de $chaine_classes</i>).<br />Si vous confirmez, ces données seront écrasées avec les nouvelles données (<i>si vous ne cochez pas les mêmes classes, les données seront seulement complétées</i>).</p>\n";
			}
			else{
				echo "<p>Des données ont déjà été sauvegardées pour l'année $annee_scolaire (<i>classe de $chaine_classes</i>).<br />Si vous confirmez, ces données seront écrasées avec les nouvelles données (<i>si vous ne cochez pas les mêmes classes, les données seront seulement complétées</i>).</p>\n";
			}

			echo "<input type='hidden' name='annee_scolaire' value='$annee_scolaire' />\n";

			echo "<center><input type=\"submit\" name='confirmer' value=\"Confirmer\" style=\"font-variant: small-caps;\" /></center>\n";
			echo "</form>\n";
			require("../lib/footer.inc.php");
			die();
		}
	}

	if(!isset($id_classe)) {
		echo "</p></div>\n";

		echo "<h2>Choix des classes</h2>\n";

		echo "<p>Conservation des données pour l'année scolaire: $annee_scolaire</p>\n";

		echo "<p>Choisissez les classes dont vous souhaitez archiver les résultats, appréciations,...</p>";
		echo "<p>Tout <a href='javascript:modif_coche(true)'>cocher</a> / <a href='javascript:modif_coche(false)'>décocher</a>.</p>";


		// Afficher les classes pour lesquelles les données sont déjà migrées...

		$sql="SELECT id,classe FROM classes ORDER BY classe";
		$res1=mysqli_query($GLOBALS["mysqli"], $sql);
		$nb_classes=mysqli_num_rows($res1);
		if($nb_classes==0){
			echo "<p>ERREUR: Il semble qu'aucune classe ne soit encore définie.</p>\n";
			require("../lib/footer.inc.php");
			die();
		}

		// Affichage sur 3 colonnes
		$nb_classes_par_colonne=round($nb_classes/3);

		echo "<table width='100%' summary='Choix des classes'>\n";
		echo "<tr valign='top' align='center'>\n";

		$i = 0;

		echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
		echo "<td align='left'>\n";

		while ($i < $nb_classes) {

			if(($i>0)&&(round($i/$nb_classes_par_colonne)==$i/$nb_classes_par_colonne)){
				echo "</td>\n";
				echo "<td align='left'>\n";
			}

			$lig_classe=mysqli_fetch_object($res1);

			echo "<input type='checkbox' id='classe".$i."' name='id_classe[]' value='$lig_classe->id' /><label for='classe".$i."' style='cursor:pointer;'> $lig_classe->classe</label><br />\n";

			$i++;
		}
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<script type='text/javascript'>
			function modif_coche(statut){
				for(k=0;k<$i;k++){
					if(document.getElementById('classe'+k)){
						document.getElementById('classe'+k).checked=statut;
					}
				}
				//changement();
			}
		</script>\n";

		echo add_token_field();

		echo "<input type='hidden' name='annee_scolaire' value='$annee_scolaire' />\n";
		echo "<input type='hidden' name='confirmer' value='ok' />\n";
		echo "<center><input type=\"submit\" name='ok' value=\"Valider\" style=\"font-variant: small-caps;\" /></center>\n";

	}
	else {
		echo "<a href='".$_SERVER['PHP_SELF']."'>Choisir d'autres classes</a> | ";
		echo "</div>\n";

		if(count($id_classe)==0){
			echo "<p>ERREUR: Vous n'avez pas coché de classe.</p>\n";
			echo "</form>\n";
			require("../lib/footer.inc.php");
			die();
		}

		check_token(false);

		/*
		echo "<p>Mise à jour du calcul du rang des élèves dans les matières...</p>\n";
		include "../lib/periodes.inc.php";
		include("../lib/calcul_rang.inc.php");
		*/


		$temoin_ects="n";
		$sql="SELECT 1=1 FROM ects_credits LIMIT 1";
		$test1=mysqli_query($GLOBALS["mysqli"], $sql);
		if(mysqli_num_rows($test1)>0) {$temoin_ects="y";}
		else {
			$sql="SELECT 1=1 FROM ects_global_credits LIMIT 1";
			$test2=mysqli_query($GLOBALS["mysqli"], $sql);
			if(mysqli_num_rows($test2)>0) {$temoin_ects="y";}
		}


		//===================================

		if(isset($deja_traitee_id_classe)){
			echo "<p>Classes déjà traitées: ";

			echo "<input type='hidden' name='deja_traitee_id_classe[]' value='$deja_traitee_id_classe[0]' />";
			echo get_nom_classe($deja_traitee_id_classe[0]);

			for($i=1;$i<count($deja_traitee_id_classe);$i++){
				echo "<input type='hidden' name='deja_traitee_id_classe[]' value='$deja_traitee_id_classe[$i]' />";
				echo ", ".get_nom_classe($deja_traitee_id_classe[$i]);
			}
			echo "</p>\n";
		}

		$temoin_encore_des_classes=0;
		//$chaine="";
		$chaine=get_nom_classe($id_classe[0]);
		for($i=1;$i<count($id_classe);$i++){
			echo "<input type='hidden' name='id_classe[]' value='$id_classe[$i]' />\n";
			$temoin_encore_des_classes++;

			$chaine.=", ".get_nom_classe($id_classe[$i]);
		}
		//if($chaine!=""){
		if($temoin_encore_des_classes>0) {
			// Pour faire sauter un "' ":
			//echo "<p>Classes restant à traiter: ".mb_substr($chaine,2)."</p>\n";
			echo "<p>Classes restant à traiter: ".$chaine."</p>\n";
		}
		else {
			echo "<p>Traitement de la dernière classe sélectionnée: <span id='annonce_fin_traitement' style='font-weight:bold; font-size:2em; color: green;'></span></p>\n";
		}
		//===================================



		$classe=get_nom_classe($id_classe[0]);

		echo "<p><b>Classe de $classe:</b></p>\n";

		//echo "<p>Classe de $classe</p>\n";

		// Boucle sur les périodes de la classe
		$sql="SELECT * FROM periodes WHERE id_classe='".$id_classe[0]."' ORDER BY num_periode";
		$res_periode=mysqli_query($GLOBALS["mysqli"], $sql);

		if(mysqli_num_rows($res_periode)==0){
			echo "<p>Aucune période ne semble définie pour la classe $classe</p>\n";
		}
		else{
			unset($tab_periode);
			$tab_periode=array();
			//$cpt=0;
			$cpt=1;
			while($lig_periode=mysqli_fetch_object($res_periode)){
				$tab_periode[$cpt]=$lig_periode->nom_periode;
				$cpt++;
			}



			$sql="SELECT DISTINCT e.* FROM eleves e,j_eleves_classes jec WHERE id_classe='".$id_classe[0]."' AND jec.login=e.login ORDER BY login";
			//echo "$sql<br />\n";
			$res_ele=mysqli_query($GLOBALS["mysqli"], $sql);

			if(mysqli_num_rows($res_ele)==0){
				echo "<p>Aucun élève dans la classe $classe???</p>\n";
			}
			else{

				if(getSettingAOui('active_mod_engagements')) {
					$tab_engagements=get_tab_engagements("eleve");
					$tab_engagements_classe=get_tab_engagements_user("", $id_classe[0], "eleve");
					/*
					echo "\$tab_engagements<pre>";
					print_r($tab_engagements);
					echo "</pre>";
					echo "\$tab_engagements_classe<pre>";
					print_r($tab_engagements_classe);
					echo "</pre>";
					*/
				}

				unset($tab_eleve);
				$tab_eleve=array();
				$cpt=0;
				while($lig_ele=mysqli_fetch_object($res_ele)){
					// Infos élève
					$tab_eleve[$cpt]=array();

					$tab_eleve[$cpt]['nom']=$lig_ele->nom;
					$tab_eleve[$cpt]['prenom']=$lig_ele->prenom;
					$tab_eleve[$cpt]['naissance']=$lig_ele->naissance;
					$tab_eleve[$cpt]['naissance2']=formate_date($lig_ele->naissance);
					$tab_eleve[$cpt]['login_eleve']=$lig_ele->login;
					$tab_eleve[$cpt]['mef_code']=$lig_ele->mef_code;

					$tab_eleve[$cpt]['ine']=$lig_ele->no_gep;

					if($tab_eleve[$cpt]['ine']==""){
						$tab_eleve[$cpt]['ine']="LOGIN_".$tab_eleve[$cpt]['login_eleve'];
						$tab_eleve[$cpt]['ine'] = cree_substitut_INE_unique($tab_eleve[$cpt]['ine']);
					}

					// On vérifie que l'élève est enregistré dans archive_eleves. Sinon, on l'enregistre
			
					$error_enregistre_eleve[$tab_eleve[$cpt]['login_eleve']] = insert_eleve($tab_eleve[$cpt]['login_eleve'],$tab_eleve[$cpt]['ine'],$annee_scolaire,'y');
					//echo "insert_eleve(\$tab_eleve[$cpt]['login_eleve'],\$tab_eleve[$cpt]['ine'],$annee_scolaire,'y') soit insert_eleve(".$tab_eleve[$cpt]['login_eleve'].",".$tab_eleve[$cpt]['ine'].",$annee_scolaire,'y')<br />";

					// Statut de redoublant ou non:
					$sql="SELECT * FROM j_eleves_regime WHERE login='".$tab_eleve[$cpt]['login_eleve']."'";
					$res_red=mysqli_query($GLOBALS["mysqli"], $sql);

					if(mysqli_num_rows($res_red)==0){
						$tab_eleve[$cpt]['doublant']="-";
					}
					else{
						$lig_red=mysqli_fetch_object($res_red);
						$tab_eleve[$cpt]['doublant']=$lig_red->doublant;
					}


					// CPE associé(s) à l'élève
					$sql="SELECT jec.cpe_login, u.nom, u.prenom, u.numind, u.type FROM j_eleves_cpe jec, utilisateurs u WHERE jec.e_login='".$tab_eleve[$cpt]['login_eleve']."' AND jec.cpe_login=u.login;";
					$res_cpe=mysqli_query($GLOBALS["mysqli"], $sql);

					if(mysqli_num_rows($res_cpe)==0){
						$tab_eleve[$cpt]['cpe']="";
						$tab_eleve[$cpt]['nom_cpe']="";
						$tab_eleve[$cpt]['prenom_cpe']="";
						$tab_eleve[$cpt]['numind_cpe']="";
						$tab_eleve[$cpt]['type_cpe']="";
					}
					else{
						$lig_cpe=mysqli_fetch_object($res_cpe);
						$tab_eleve[$cpt]['cpe']=affiche_utilisateur($lig_cpe->cpe_login,$id_classe[0]);
						$tab_eleve[$cpt]['nom_cpe']=$lig_cpe->nom;
						$tab_eleve[$cpt]['prenom_cpe']=$lig_cpe->prenom;
						$tab_eleve[$cpt]['numind_cpe']=$lig_cpe->numind;
						$tab_eleve[$cpt]['type_cpe']=$lig_cpe->type;

						// Normalement, il n'y a qu'un CPE associé
						while($lig_cpe=mysqli_fetch_object($res_cpe)){
							$tab_eleve[$cpt]['cpe'].=", ".affiche_utilisateur($lig_cpe->cpe_login,$id_classe[0]);
							$tab_eleve[$cpt]['nom_cpe'].="|".$lig_cpe->nom;
							$tab_eleve[$cpt]['prenom_cpe'].="|".$lig_cpe->prenom;
							$tab_eleve[$cpt]['numind_cpe'].="|".$lig_cpe->numind;
							$tab_eleve[$cpt]['type_cpe'].="|".$lig_cpe->type;
						}
					}



					$cpt++;
				}



				// Personne assurant le suivi de la classe...
				$sql="SELECT suivi_par FROM classes WHERE id='$id_classe[0]'";
				$res_suivi=mysqli_query($GLOBALS["mysqli"], $sql);
				if(mysqli_num_rows($res_suivi)==0){
					$suivi_par="-";
				}
				else{
					$lig_suivi=mysqli_fetch_object($res_suivi);
					$suivi_par=$lig_suivi->suivi_par;
				}



				echo "<table class='boireaus' border='1' summary='Tableau des élèves'>\n";

				// Boucle sur les périodes
				echo "<tr>\n";
				echo "<th>Élève</th>\n";
				//for($i=0;$i<count($tab_periode);$i++){
				for($i=1;$i<=count($tab_periode);$i++){
					echo "<th>$tab_periode[$i]</th>\n";
				}
				echo "</tr>\n";

				// Boucle sur les élèves
				$alt=1;
				for($j=0;$j<count($tab_eleve);$j++){
					$alt=$alt*(-1);
					echo "<tr class='lig$alt'>\n";
					echo "<td id='td_0_".$j."'>".$tab_eleve[$j]['nom']." ".$tab_eleve[$j]['prenom']."</td>\n";
					//for($i=0;$i<count($tab_periode);$i++){
					for($i=1;$i<=count($tab_periode);$i++){
						echo "<td id='td_".$i."_".$j."'>&nbsp;</td>\n";
					}
					echo "</tr>\n";
				}

				echo "</table>\n";


				// Début du traitement

				// Répartitions annuelles
				$moy_eleve_groupe_annee=array();

				$tab_prof_grp=array();
				$mat_grp=array();
				$code_mat_grp=array();

				$tab_archivage_engagements=array();
				for($i=1;$i<=count($tab_periode);$i++){
					// Nettoyage:
					$sql="DELETE FROM archivage_disciplines WHERE annee='$annee_scolaire' AND classe='$classe' AND num_periode='$i';";
					enregistrer_sql_archivage($sql);
					$res_nettoyage=mysqli_query($GLOBALS["mysqli"], $sql);

					if(!$res_nettoyage){
						echo "<p style='color:red'><b>ERREUR</b> lors du nettoyage</p>\n";
						echo "</form>\n";
						require("../lib/footer.inc.php");
						die();
					}

					$erreur=0;

					$num_periode=$i;
					$nom_periode=$tab_periode[$i];


					// Calculer les moyennes de classe, rechercher min et max pour tous les groupes associés à la classe sur la période.
					//$sql="SELECT DISTINCT id_groupe, priorite FROM j_groupes_classes WHERE id_classe='".$id_classe[0]."'";
					// Problème avec les élèves qui changent de classe: Il faut récupérer les effectifs,... de tous les groupes associés à des élèves de la classe
					$sql="SELECT DISTINCT jeg.id_groupe FROM j_eleves_classes jec, 
														j_eleves_groupes jeg 
													WHERE jec.id_classe='".$id_classe[0]."' AND 
														jec.login=jeg.login;";
					//echo "$sql<br />";
					$res_groupes=mysqli_query($GLOBALS["mysqli"], $sql);

					$moymin=array();
					$moymax=array();
					$moyclasse=array();
					$ordre_matiere=array();

					$eff_groupe=array();
					$repar_moins_8=array();
					$repar_8_12=array();
					$repar_plus_12=array();

					if(mysqli_num_rows($res_groupes)==0){
						// Dans ce cas, il ne doit pas y avoir de note,... pour les élèves
					}
					else{
						while($lig_groupes=mysqli_fetch_object($res_groupes)){
							$id_groupe=$lig_groupes->id_groupe;

							$sql="SELECT priorite FROM j_groupes_classes WHERE id_classe='".$id_classe[0]."' AND id_groupe='$id_groupe';";
							$res_grp_ordre=mysqli_query($GLOBALS["mysqli"], $sql);
							if(mysqli_num_rows($res_grp_ordre)>0) {
								$lig_grp_ordre=mysqli_fetch_object($res_grp_ordre);
								$ordre_matiere[$id_groupe]=$lig_grp_ordre->priorite;
							}
							else {
								$sql="SELECT priorite FROM j_groupes_classes WHERE id_groupe='$id_groupe';";
								$res_grp_ordre=mysqli_query($GLOBALS["mysqli"], $sql);
								if(mysqli_num_rows($res_grp_ordre)>0) {
									$lig_grp_ordre=mysqli_fetch_object($res_grp_ordre);
									$ordre_matiere[$id_groupe]=$lig_grp_ordre->priorite;
								}
								else {
									$ordre_matiere[$id_groupe]="";
								}
							}

							$sql="SELECT 1=1 FROM matieres_notes WHERE id_groupe='$id_groupe' AND periode='$i'";
							//echo "$sql<br />\n";
							$res_eff=mysqli_query($GLOBALS["mysqli"], $sql);
							$eff_groupe[$id_groupe]=mysqli_num_rows($res_eff);

							$repar_moins_8[$id_groupe]="";
							$repar_8_12[$id_groupe]="";
							$repar_plus_12[$id_groupe]="";

							if($eff_groupe[$id_groupe]>0) {
								$sql="SELECT 1=1 FROM matieres_notes WHERE id_groupe='$id_groupe' AND statut='' AND note<'8' AND periode='$i'";
								//echo "$sql<br />\n";
								$res_eff=mysqli_query($GLOBALS["mysqli"], $sql);
								$repar_moins_8[$id_groupe]=100*mysqli_num_rows($res_eff)/$eff_groupe[$id_groupe];

								$sql="SELECT 1=1 FROM matieres_notes WHERE id_groupe='$id_groupe' AND statut='' AND note>='8' AND note<'12' AND periode='$i'";
								//echo "$sql<br />\n";
								$res_eff=mysqli_query($GLOBALS["mysqli"], $sql);
								$repar_8_12[$id_groupe]=100*mysqli_num_rows($res_eff)/$eff_groupe[$id_groupe];

								$sql="SELECT 1=1 FROM matieres_notes WHERE id_groupe='$id_groupe' AND statut='' AND note>='12' AND periode='$i'";
								//echo "$sql<br />\n";
								$res_eff=mysqli_query($GLOBALS["mysqli"], $sql);
								$repar_plus_12[$id_groupe]=100*mysqli_num_rows($res_eff)/$eff_groupe[$id_groupe];
							}

							$sql="SELECT AVG(note) moyenne FROM matieres_notes WHERE id_groupe='$id_groupe' AND statut='' AND periode='$i'";
							//echo "$sql<br />\n";
							$res_moy=mysqli_query($GLOBALS["mysqli"], $sql);
							if(mysqli_num_rows($res_moy)==0){
								$moyclasse[$id_groupe]="-";
							}
							else{
								$lig_moy=mysqli_fetch_object($res_moy);
								$moyclasse[$id_groupe]=round($lig_moy->moyenne*10)/10;
							}

							$sql="SELECT MAX(note) moyenne FROM matieres_notes WHERE id_groupe='$id_groupe' AND statut='' AND periode='$i'";
							$res_moy=mysqli_query($GLOBALS["mysqli"], $sql);
							if(mysqli_num_rows($res_moy)==0){
								$moymax[$id_groupe]="-";
							}
							else{
								$lig_moy=mysqli_fetch_object($res_moy);
								$moymax[$id_groupe]=$lig_moy->moyenne;
							}

							$sql="SELECT MIN(note) moyenne FROM matieres_notes WHERE id_groupe='$id_groupe' AND statut='' AND periode='$i'";
							$res_moy=mysqli_query($GLOBALS["mysqli"], $sql);
							if(mysqli_num_rows($res_moy)==0){
								$moymin[$id_groupe]="-";
							}
							else{
								$lig_moy=mysqli_fetch_object($res_moy);
								$moymin[$id_groupe]=$lig_moy->moyenne;
							}
						}
					}
					/*
					echo "<pre>";
					print_r($eff_groupe);
					echo "</pre>";
					*/

					// Boucle sur les élèves
					for($j=0;$j<count($tab_eleve);$j++){
						$ine=$tab_eleve[$j]['ine'];
						$nom=$tab_eleve[$j]['nom'];
						$prenom=$tab_eleve[$j]['prenom'];
						$naissance=$tab_eleve[$j]['naissance'];
						$naissance2=$tab_eleve[$j]['naissance2'];
						$login_eleve=$tab_eleve[$j]['login_eleve'];
						$doublant=$tab_eleve[$j]['doublant'];
						$cpe=$tab_eleve[$j]['cpe'];
						if ($error_enregistre_eleve[$login_eleve] != '') {
							echo "<script type='text/javascript'>
	document.getElementById('td_0_".$j."').style.backgroundColor='red';
</script>\n";
						}

						$mef_code=$tab_eleve[$j]['mef_code'];


						if(getSettingAOui('active_mod_engagements')) {
							if(!in_array($ine, $tab_archivage_engagements)) {
								$sql="DELETE FROM archivage_engagements WHERE annee='$annee_scolaire' AND ine='$ine';";
								enregistrer_sql_archivage($sql);
								//echo "$sql<br />";
								$menage=mysqli_query($GLOBALS["mysqli"], $sql);

								if(array_key_exists($login_eleve, $tab_engagements_classe['login_user'])) {
									for($loop=0;$loop<count($tab_engagements_classe['login_user'][$login_eleve]);$loop++) {
										$compteur_courant=$tab_engagements_classe['login_user'][$login_eleve][$loop];
										$sql="INSERT INTO archivage_engagements SET annee='$annee_scolaire',
																	ine='$ine',
																	code_engagement='".$tab_engagements_classe['indice'][$compteur_courant]['code_engagement']."',
																	nom_engagement='".addslashes($tab_engagements_classe['indice'][$compteur_courant]['nom_engagement'])."',
																	description_engagement='".addslashes($tab_engagements_classe['indice'][$compteur_courant]['engagement_description'])."'";
										if(($tab_engagements_classe['indice'][$compteur_courant]['id_type']=='id_classe')&&($tab_engagements_classe['indice'][$compteur_courant]['valeur']!="")) {
											$sql.=",classe='".addslashes(get_nom_classe($tab_engagements_classe['indice'][$compteur_courant]['valeur']))."'";
										}
										$sql.=";";
										enregistrer_sql_archivage($sql);
										//echo "$sql<br />";
										$insert=mysqli_query($GLOBALS["mysqli"], $sql);
										if(!$insert){
											$erreur++;

											echo "<script type='text/javascript'>
	document.getElementById('td_".$i."_".$j."').style.backgroundColor='red';
</script>\n";
										}
									}
								}

								$tab_archivage_engagements[]=$ine;
							}
						}

						$sql="SELECT * FROM j_eleves_classes WHERE id_classe='".$id_classe[0]."' AND periode='$i'";
						$res_eff=mysqli_query($GLOBALS["mysqli"], $sql);
						$eff_classe=mysqli_num_rows($res_eff);

						// Absences, retards,... de l'élève
						$sql="SELECT * FROM absences WHERE login='".$login_eleve."' AND periode='$i'";
						$res_abs=mysqli_query($GLOBALS["mysqli"], $sql);

						if(mysqli_num_rows($res_abs)==0){
							$nb_absences="-";
							$non_justifie="-";
							$nb_retards="-";
							$appreciation="-";
						}
						else{
							$lig_abs=mysqli_fetch_object($res_abs);
							$nb_absences=$lig_abs->nb_absences;
							$non_justifie=$lig_abs->non_justifie;
							$nb_retards=$lig_abs->nb_retards;
							$appreciation=$lig_abs->appreciation;
						}

						$sql="INSERT INTO archivage_disciplines SET
											annee='$annee_scolaire',
											ine='$ine',
											classe='".addslashes($classe)."',
											effectif='$eff_classe',
											mef_code='$mef_code',
											num_periode='$num_periode',
											nom_periode='".addslashes($nom_periode)."',
											special='ABSENCES',
											matiere='',
											code_matiere='',
											id_prof='".$tab_eleve[$j]['numind_cpe']."',
											type_prof='".$tab_eleve[$j]['type_cpe']."',
											prof='".addslashes($cpe)."',
											nom_prof='".addslashes($tab_eleve[$j]['nom_cpe'])."',
											prenom_prof='".addslashes($tab_eleve[$j]['prenom_cpe'])."',
											note='',
											moymin='',
											moymax='',
											moyclasse='',
											repar_moins_8='',
											repar_8_12='',
											repar_plus_12='',
											appreciation='".addslashes($appreciation)."',
											nb_absences='$nb_absences',
											non_justifie='$non_justifie',
											nb_retards='$nb_retards'
											;";
						enregistrer_sql_archivage($sql);
						echo "<!-- $sql -->\n";
						$res_insert=mysqli_query($GLOBALS["mysqli"], $sql);

						if(!$res_insert){
							$erreur++;

							//echo "<span style='color:red'>$sql</span><br />";

							echo "<script type='text/javascript'>
	document.getElementById('td_".$i."_".$j."').style.backgroundColor='red';
</script>\n";
						}




						// Avis du conseil de classe
						$sql="SELECT * FROM avis_conseil_classe WHERE login='$login_eleve' AND periode='$num_periode'";
						$res_avis=mysqli_query($GLOBALS["mysqli"], $sql);

						if(mysqli_num_rows($res_avis)==0){
							$avis="-";
						}
						else{
							$lig_avis=mysqli_fetch_object($res_avis);
							$avis=$lig_avis->avis;
							// A quoi sert le champ statut de la table avis_conseil_classe ?
						}

						// Insertion de l'avis dans archivage_disciplines
						$sql="INSERT INTO archivage_disciplines SET
											annee='$annee_scolaire',
											ine='$ine',
											classe='".addslashes($classe)."',
											effectif='$eff_classe',
											mef_code='$mef_code',
											num_periode='$num_periode',
											nom_periode='".addslashes($nom_periode)."',
											special='AVIS_CONSEIL',
											matiere='',
											code_matiere='',
											prof='".addslashes($suivi_par)."',
											nom_prof='',
											prenom_prof='',
											note='',
											moymin='',
											moymax='',
											moyclasse='',
											repar_moins_8='',
											repar_8_12='',
											repar_plus_12='',
											appreciation='".addslashes($avis)."',
											nb_absences='',
											non_justifie='',
											nb_retards=''
											;";
						echo "<!-- $sql -->\n";
						enregistrer_sql_archivage($sql);
						$res_insert=mysqli_query($GLOBALS["mysqli"], $sql);

						if(!$res_insert){
							$erreur++;

							//echo "<span style='color:red'>$sql</span><br />";

							echo "<script type='text/javascript'>
	document.getElementById('td_".$i."_".$j."').style.backgroundColor='red';
</script>\n";
						}




						// Boucle sur les matières de l'élève
						/*
						$sql="SELECT mn.*,g.description FROM groupes g,matieres_notes mn
														WHERE login='$login_eleve' AND
																periode='$num_periode'";
						*/
						/*
						$sql="SELECT mn.*,m.nom_complet FROM j_groupes_matieres jgm,matieres m,matieres_notes mn
														WHERE mn.login='$login_eleve' AND
																mn.periode='$num_periode' AND
																jgm.id_groupe=mn.id_groupe AND
																jgm.id_matiere=m.matiere;";
						*/
						$sql="SELECT jeg.id_groupe, m.nom_complet, m.code_matiere FROM j_groupes_matieres jgm,matieres m,j_eleves_groupes jeg
														WHERE jeg.login='$login_eleve' AND
																jeg.periode='$num_periode' AND
																jgm.id_groupe=jeg.id_groupe AND
																jgm.id_matiere=m.matiere;";
						//echo "$sql<br />";
						echo "<!-- $sql -->\n";
						$res_grp=mysqli_query($GLOBALS["mysqli"], $sql);

						if(mysqli_num_rows($res_grp)==0){
							// Que faire? Est-il possible qu'il y ait quelque chose dans matieres_appreciations dans ce cas?
							// Ca ne devrait pas...
							// Si... on peut avoir un professeur qui n'a pas saisi de note ni même un tiret (malheureusement), mais mis une appréciation
							//echo "<!-- Aucune note sur le bulletin de période $num_periode pour l'élève $login_eleve -->\n";

							echo "<!-- En période $num_periode, l'élève $login_eleve n'est associé à aucun enseignement -->\n";
						}
						else{
							while($lig_grp=mysqli_fetch_object($res_grp)){

								$id_groupe=$lig_grp->id_groupe;
								$matiere=$lig_grp->nom_complet;
								$code_matiere=$lig_grp->code_matiere;

								$mat_grp[$id_groupe]=$matiere;
								$code_mat_grp[$id_groupe]=$code_matiere;

								$sql="SELECT mn.* FROM matieres_notes mn
														WHERE mn.login='$login_eleve' AND
																mn.periode='$num_periode' AND
																mn.id_groupe='$id_groupe';";
								$res_note=mysqli_query($GLOBALS["mysqli"], $sql);
								if(mysqli_num_rows($res_note)==0) {
									$note='';
									$rang=-1;
								}
								else {
									$lig_note=mysqli_fetch_object($res_note);

									if($lig_note->statut!=''){
										$note=$lig_note->statut;
									}
									else{
										$note=$lig_note->note;

										$moy_eleve_groupe_annee[$id_groupe][$ine][]=$note;
									}
									$rang=$lig_note->rang;
								}

								// Récupération de l'appréciation
								$sql="SELECT appreciation FROM matieres_appreciations
														WHERE login='$login_eleve' AND
																periode='$num_periode' AND
																id_groupe='$id_groupe'";
								echo "<!-- $sql -->\n";
								$res_app=mysqli_query($GLOBALS["mysqli"], $sql);

								if(mysqli_num_rows($res_app)==0){
									$appreciation="-";
								}
								else{
									$lig_app=mysqli_fetch_object($res_app);
									$appreciation=$lig_app->appreciation;
								}

								if(($note!='')||($appreciation!='-')) {
									// Récupération des professeurs associés
									if(isset($tab_prof_grp[$id_groupe])) {
										$prof=$tab_prof_grp[$id_groupe]['prof'];
										$nom_prof=$tab_prof_grp[$id_groupe]['nom_prof'];
										$prenom_prof=$tab_prof_grp[$id_groupe]['prenom_prof'];
										$numind_prof=$tab_prof_grp[$id_groupe]['numind_prof'];
										$type_prof=$tab_prof_grp[$id_groupe]['type_prof'];
									}
									else {
										$sql="SELECT u.login, u.nom,u.prenom, u.numind, u.type FROM j_groupes_professeurs jgp, utilisateurs u WHERE jgp.id_groupe='$id_groupe' AND jgp.login=u.login ORDER BY login";
										echo "<!-- $sql -->\n";
										//echo "$sql<br />";
										$res_prof=mysqli_query($GLOBALS["mysqli"], $sql);
	
										$nom_prof="";
										$prenom_prof="";
										if(mysqli_num_rows($res_prof)==0){
											$prof="";
										}
										else{
											$lig_prof=mysqli_fetch_object($res_prof);
											$prof=affiche_utilisateur($lig_prof->login,$id_classe[0]);
											$nom_prof=$lig_prof->nom;
											$prenom_prof=$lig_prof->prenom;
											$numind_prof=$lig_prof->numind;
											$type_prof=$lig_prof->type;
											while($lig_prof=mysqli_fetch_object($res_prof)){
												$prof.=", ".affiche_utilisateur($lig_prof->login,$id_classe[0]);
												$nom_prof.="|".$lig_prof->nom;
												$prenom_prof.="|".$lig_prof->prenom;
												$numind_prof.="|".$lig_prof->numind;
												$type_prof.="|".$lig_prof->type;
											}
										}
										$tab_prof_grp[$id_groupe]['prof']=$prof;
										$tab_prof_grp[$id_groupe]['nom_prof']=$nom_prof;
										$tab_prof_grp[$id_groupe]['prenom_prof']=$prenom_prof;
										$tab_prof_grp[$id_groupe]['numind_prof']=$numind_prof;
										$tab_prof_grp[$id_groupe]['type_prof']=$type_prof;
									}
	
									// Insertion de la note, l'appréciation,... dans la matière,...
									if (!isset($moymin[$id_groupe])) $moymin[$id_groupe]="-";
									if (!isset($moymax[$id_groupe])) $moymax[$id_groupe]="-";
									if (!isset($moyclasse[$id_groupe])) $moyclasse[$id_groupe]="-";

									$sql="INSERT INTO archivage_disciplines SET
														annee='$annee_scolaire',
														ine='$ine',
														classe='".addslashes($classe)."',
														mef_code='$mef_code',
														effectif='".$eff_groupe[$id_groupe]."',
														num_periode='$num_periode',
														nom_periode='".addslashes($nom_periode)."',
														matiere='".addslashes($matiere)."',
														code_matiere='".addslashes($code_matiere)."',
														id_groupe='$id_groupe',
														special='',
														id_prof='".$numind_prof."',
														type_prof='".$type_prof."',
														prof='".addslashes($prof)."',
														nom_prof='".addslashes($nom_prof)."',
														prenom_prof='".addslashes($prenom_prof)."',
														note='$note',
														moymin='".$moymin[$id_groupe]."',
														moymax='".$moymax[$id_groupe]."',
														moyclasse='".$moyclasse[$id_groupe]."',
														repar_moins_8='".$repar_moins_8[$id_groupe]."',
														repar_8_12='".$repar_8_12[$id_groupe]."',
														repar_plus_12='".$repar_plus_12[$id_groupe]."',
														rang='".$rang."',
														appreciation='".addslashes($appreciation)."',
														nb_absences='',
														non_justifie='',
														nb_retards='',
														ordre_matiere='".$ordre_matiere[$id_groupe]."'
														;";
									echo "<!-- $sql -->\n";
									enregistrer_sql_archivage($sql);
									$res_insert=mysqli_query($GLOBALS["mysqli"], $sql);
	
									if(!$res_insert){
										$erreur++;

										//echo "<span style='color:red'>$sql</span><br />";

										echo "<script type='text/javascript'>
	document.getElementById('td_".$i."_".$j."').style.backgroundColor='red';
</script>\n";
									}
								}

							} // Fin de la boucle matières


							echo "<!-- Avant les crédits ECTS de l'élève $login_eleve -->\n";

							if($temoin_ects=="y") {
								//--------------------
								// Les crédits ECTS
								//--------------------
	
								// On a besoin de : annee, ine, classe, num_periode, nom_periode, matiere, prof, valeur_ects, mention_ects
								// On a déjà pratiquement tout... ça ne va pas être compliqué !
								$Eleve = ElevePeer::retrieveByLOGIN($login_eleve);
								$Groupes = $Eleve->getGroupes($num_periode);
	
								foreach($Groupes as $Groupe) {
									
									$Ects = $Eleve->getEctsCredit($num_periode,$Groupe->getId());
	
									if ($Ects != null) {
										$Archive = new ArchiveEcts();
										$Archive->setAnnee($annee_scolaire);
										$Archive->setIne($ine);
										$Archive->setClasse($classe);
										$Archive->setNumPeriode($num_periode);
										$Archive->setNomPeriode($nom_periode);
										$Archive->setMatiere($Groupe->getDescription());
										$Archive->setSpecial('');
										$Archive->setProfs($prof);
										$Archive->setValeur($Ects->getValeur());
										$Archive->setMention($Ects->getMention());
										$Archive->save();
									}
								}
							}
							echo "<!-- Après les crédits ECTS de l'élève $login_eleve -->\n";

							if($erreur==0){
								echo "<script type='text/javascript'>
	document.getElementById('td_".$i."_".$j."').style.backgroundColor='green';
</script>\n";
							}
							flush();
						}

					}

				}

				foreach($moy_eleve_groupe_annee as $id_groupe => $tab) {
					if(count($tab)>0) {

						foreach($tab as $ine => $note) {
							if(count($note)>0) {
								$moyenne_annuelle_eleve[$ine]=array_sum($note)/count($note);
							}
						}

						if(count($moyenne_annuelle_eleve)>0) {
							$moyenne_annuelle_grp=array_sum($moyenne_annuelle_eleve)/count($moyenne_annuelle_eleve);
							$moymin_annuelle_grp=min($moyenne_annuelle_eleve);
							$moymax_annuelle_grp=max($moyenne_annuelle_eleve);

							/*
							echo "$id_groupe \$tab<pre>";
							echo "=======================\n";
							print_r($tab);
							echo "=======================\n";
							print_r($moyenne_annuelle_eleve);
							echo "=======================\n";
							echo "</pre>";
							*/

							$repar_moins_8_annee=0;
							$repar_8_12_annee=0;
							$repar_plus_12_annee=0;
							foreach($moyenne_annuelle_eleve as $ine => $note) {
								if($note<8) {
									$repar_moins_8_annee++;
								}
								elseif($note>=12) {
									$repar_plus_12_annee++;
								}
								else {
									$repar_8_12_annee++;
								}
							}
							$repar_moins_8_annee=100*$repar_moins_8_annee/count($moyenne_annuelle_eleve);
							$repar_8_12_annee=100*$repar_8_12_annee/count($moyenne_annuelle_eleve);
							//$repar_plus_12_annee=100*$repar_plus_12_annee/count($moyenne_annuelle_eleve);
							// Pour éviter des pb d'arrondi à deux chiffres dans la table mysql:
							$repar_plus_12_annee=100-$repar_moins_8_annee-$repar_8_12_annee;

							// Pour éviter les scories si on fait plusieurs archivages d'une même année:
							$sql="DELETE FROM archivage_disciplines WHERE 
												annee='$annee_scolaire' AND 
												ine='' AND 
												classe='".addslashes($classe)."' AND 
												mef_code='' AND 
												num_periode='' AND 
												nom_periode='ANNEE' AND 
												matiere='".addslashes($mat_grp[$id_groupe])."' AND 
												code_matiere='".addslashes($code_mat_grp[$id_groupe])."' AND 
												id_groupe='$id_groupe' AND 
												special='GRP_ANNEE';";
							enregistrer_sql_archivage($sql);
							$menage=mysqli_query($GLOBALS["mysqli"], $sql);

							$sql="INSERT INTO archivage_disciplines SET
												annee='$annee_scolaire',
												ine='',
												classe='".addslashes($classe)."',
												mef_code='',
												effectif='".$eff_groupe[$id_groupe]."',
												num_periode='',
												nom_periode='ANNEE',
												matiere='".addslashes($mat_grp[$id_groupe])."',
												code_matiere='".addslashes($code_mat_grp[$id_groupe])."',
												id_groupe='$id_groupe',
												special='GRP_ANNEE',
												id_prof='".$tab_prof_grp[$id_groupe]['numind_prof']."',
												type_prof='".$tab_prof_grp[$id_groupe]['type_prof']."',
												prof='".addslashes($tab_prof_grp[$id_groupe]['prof'])."',
												nom_prof='".addslashes($tab_prof_grp[$id_groupe]['nom_prof'])."',
												prenom_prof='".addslashes($tab_prof_grp[$id_groupe]['prenom_prof'])."',
												note='',
												moymin='".$moymin_annuelle_grp."',
												moymax='".$moymax_annuelle_grp."',
												moyclasse='".$moyenne_annuelle_grp."',
												repar_moins_8='".$repar_moins_8_annee."',
												repar_8_12='".$repar_8_12_annee."',
												repar_plus_12='".$repar_plus_12_annee."',
												rang='',
												appreciation='',
												nb_absences='',
												non_justifie='',
												nb_retards='',
												ordre_matiere='".$ordre_matiere[$id_groupe]."'
												;";
							echo "<!-- $sql -->\n";
							enregistrer_sql_archivage($sql);
							$res_insert=mysqli_query($GLOBALS["mysqli"], $sql);

							if(!$res_insert){
								$erreur++;
							}
						}
					}
				}

			}



//==================================================
//**************************************************
//==================================================

		}


		//===================================

		echo "<input type='hidden' name='deja_traitee_id_classe[]' value='$id_classe[0]' />\n";

		/*
		$temoin_encore_des_classes=0;
		$chaine="";
		for($i=1;$i<count($id_classe);$i++){
			echo "<input type='hidden' name='id_classe[]' value='$id_classe[$i]' />\n";
			$temoin_encore_des_classes++;

			$chaine.=", ".get_nom_classe($id_classe[$i]);
		}
		if($chaine!=""){
			echo "<p>Classes restant à traiter: ".mb_substr($chaine,2)."</p>\n";
		}
		*/

		if($temoin_encore_des_classes>0){
			echo "<script type='text/javascript'>
	setTimeout('document.formulaire.submit();', 5000);
</script>\n";
			echo "<center><input type=\"submit\" name='ok' value=\"Valider\" style=\"font-variant: small-caps;\" /></center>\n";
		}
		else{
			echo "<p style='text-align:center; font-weight:bold; font-size:2em; color: green;'>Traitement terminé.</p>\n";
			echo "<script type='text/javascript'>
	document.getElementById('annonce_fin_traitement').innerHTML='Traitement terminé.';
</script>\n";

		}

		echo "<input type='hidden' name='annee_scolaire' value='$annee_scolaire' />\n";
		echo "<input type='hidden' name='confirmer' value='ok' />\n";
		echo add_token_field();
		//echo "<center><input type=\"submit\" name='ok' value=\"Valider\" style=\"font-variant: small-caps;\" /></center>\n";

	//===================================


	}
}

//echo "<center><input type=\"submit\" name='ok' value=\"Valider\" style=\"font-variant: small-caps;\" /></center>\n";

echo "</form>\n";
echo "<br />\n";
require("../lib/footer.inc.php");
?>
