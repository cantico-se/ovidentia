function messages(lang)
{
	switch (lang)
		{
		case "fr":
			this.insert_table = {
				f_rows: "Vous devez entrer un nombre de ligne",
				f_cols: "Vous devez entrer un nombre de colonnes",
				title: "Insérer un tableau",
				rows: "Lignes:",
				cols: "Colonnes:",
				width: "Largeur:",
				layout: "Disposition",
				alignment: "Alignement",
				borderthick: "Bordure:",
				spacing: "Espacement",
				cellspacing: "Remplissage:",
				cellpadding: "Espacement:",
				ok: "Ok",
				cancel: "Annuler",
				notset: "Non défini",
				left: "Gauche",
				right: "Droite",
				texttop: "Text en haut",
				absmiddle: "Milieu absolu",
				baseline: "Ligne de base",
				absbottom: "Bas absolu",
				bottom: "Bas",
				middle: "Milieu",
				top: "Haut"
				};
			
			this.insert_bablink = {
				title: "Insérer un lien",
				ok: "Ok",
				cancel: "Annuler",
				notset: "Non défini",
				url: "Adresse:",
				msgurl: "Vous devez entrer une adresse"
				};
			break;

		default:
		case "en":
			this.insert_table = {
				f_rows: "You must enter a number of rows",
				f_cols: "You must enter a number of columns",
				title: "Insert Table",
				rows: "Rows:",
				cols: "Cols:",
				width: "width:",
				layout: "Layout",
				alignment: "Alignement",
				borderthick: "Border thickness:",
				spacing: "Spacing",
				cellspacing: "Cell spacing:",
				cellpadding: "Cell padding:",
				ok: "Ok",
				cancel: "Cancel",
				notset: "Not set",
				left: "Left",
				right: "Right",
				texttop: "Text Top",
				absmiddle: "Absmiddle",
				baseline: "baseline",
				absbottom: "absbottom",
				bottom: "Bottom",
				middle: "Middle",
				top: "Top"
				};
			
			this.insert_bablink = {
				title: "Insert Link",
				ok: "Ok",
				cancel: "Cancel",
				notset: "Not set",
				url: "Url:",
				msgurl: "You must enter the URL"
				};
			break;
		}
}

