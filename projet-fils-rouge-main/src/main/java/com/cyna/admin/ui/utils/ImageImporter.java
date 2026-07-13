package com.cyna.admin.ui.utils;

import javax.swing.JFileChooser;
import javax.swing.JOptionPane;
import javax.swing.filechooser.FileNameExtensionFilter;

import java.awt.Component;
import java.io.File;
import java.io.InputStream;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.nio.file.StandardCopyOption;
import java.util.Properties;

/**
 * Outil partagé d'import d'images pour le back-office.
 * Ouvre un sélecteur de fichier, copie l'image choisie dans le dossier public du site
 * (servi par Apache/Docker) et renvoie le chemin relatif à enregistrer en base.
 *
 * Le dossier racine des images est lu depuis application.properties (clé app.images.dir).
 * Chaque appel précise le sous-dossier ("products", "slides", "categories"...).
 */
public final class ImageImporter {

    private ImageImporter() {}

    /** Dossier racine des images du site (ex. .../Cyna/public/assets/img). */
    public static String baseDir() {
        try (InputStream in = ImageImporter.class.getClassLoader().getResourceAsStream("application.properties")) {
            if (in != null) {
                Properties p = new Properties();
                p.load(in);
                return p.getProperty("app.images.dir", "").trim();
            }
        } catch (Exception ignored) {}
        return "";
    }

    /**
     * Ouvre un sélecteur, copie l'image dans &lt;base&gt;/&lt;subFolder&gt;/ et renvoie le
     * chemin relatif à stocker en base (ex. "slides/ma-slide.svg").
     *
     * @param parent    composant parent pour les dialogues
     * @param subFolder sous-dossier de destination ("products", "slides"...)
     * @param baseName  nom de fichier souhaité sans extension (sinon nom d'origine)
     * @return le chemin relatif, ou null si l'utilisateur annule / en cas d'erreur
     */
    public static String chooseAndCopy(Component parent, String subFolder, String baseName) {
        String base = baseDir();
        if (base.isEmpty()) {
            JOptionPane.showMessageDialog(parent,
                "Le dossier des images n'est pas configuré.\nRenseignez 'app.images.dir' dans application.properties.",
                "Configuration manquante", JOptionPane.WARNING_MESSAGE);
            return null;
        }

        JFileChooser fc = new JFileChooser();
        fc.setDialogTitle("Choisir une image");
        fc.setAcceptAllFileFilterUsed(false);
        fc.setFileFilter(new FileNameExtensionFilter("Images web (SVG, PNG, JPG, WebP)", "svg", "png", "jpg", "jpeg", "webp"));
        if (fc.showOpenDialog(parent) != JFileChooser.APPROVE_OPTION) return null;

        File src = fc.getSelectedFile();
        String name = src.getName();
        int dot = name.lastIndexOf('.');
        String ext = (dot >= 0) ? name.substring(dot + 1).toLowerCase() : "png";
        String raw = (baseName != null && !baseName.isBlank())
            ? baseName
            : (dot >= 0 ? name.substring(0, dot) : name);
        String fileName = raw.replaceAll("[^a-zA-Z0-9-]", "-").replaceAll("-+", "-") + "." + ext;

        try {
            Path destDir = Paths.get(base, subFolder);
            Files.createDirectories(destDir);
            Files.copy(src.toPath(), destDir.resolve(fileName), StandardCopyOption.REPLACE_EXISTING);
            return subFolder + "/" + fileName;
        } catch (Exception ex) {
            ex.printStackTrace();
            JOptionPane.showMessageDialog(parent,
                "Erreur lors de la copie de l'image : " + ex.getMessage(),
                "Erreur", JOptionPane.ERROR_MESSAGE);
            return null;
        }
    }
}
