package com.cyna.admin.ui.panels;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import javax.swing.table.DefaultTableModel;

import com.cyna.admin.database.DBConnection;
import com.cyna.admin.ui.frames.MainFrame;
import com.cyna.admin.ui.utils.ImageImporter;

import java.awt.*;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;

public class ContentPanel extends JPanel {

    // 1 : ATTRIBUTS ET CONFIGURATION DU PANNEAU
    private MainFrame mainFrame;
    private DefaultTableModel carouselTableModel;
    private JTable carouselTable;
    private JTextArea fixedTextArea;

    public ContentPanel(MainFrame mainFrame) {
        this.mainFrame = mainFrame;
        setLayout(new BorderLayout(0, 20));
        setBackground(mainFrame.CLAIR_FOND_PRINCIPAL);
        setBorder(new EmptyBorder(20, 30, 20, 30));

        // 2 : STRUCTURE DU CARROUSEL ET BARRE D'ACTIONS
        JPanel carouselPanel = new JPanel(new BorderLayout(0, 10));
        carouselPanel.setOpaque(false);
        
        JPanel carouselTop = new JPanel(new BorderLayout());
        carouselTop.setOpaque(false);
        
        JLabel lblCarouselTitle = new JLabel("🖼️ Gestion du Carrousel");
        lblCarouselTitle.setFont(new Font("SansSerif", Font.BOLD, 16));
        carouselTop.add(lblCarouselTitle, BorderLayout.WEST);

        JPanel carouselActions = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        carouselActions.setOpaque(false);
        
        JButton btnCarouselAdd = new JButton("Ajouter Section");
        btnCarouselAdd.addActionListener(e -> openCarouselEditDialog(-1));
        
        JButton btnCarouselEdit = new JButton("Modifier");
        btnCarouselEdit.addActionListener(e -> {
            int row = carouselTable.getSelectedRow();
            if (row != -1) openCarouselEditDialog(row);
        });

        JButton btnCarouselUp = new JButton("⬆️ Monter");
        btnCarouselUp.addActionListener(e -> moveCarouselRow(-1));

        JButton btnCarouselDown = new JButton("⬇️ Descendre");
        btnCarouselDown.addActionListener(e -> moveCarouselRow(1));

        JButton btnCarouselDelete = new JButton("Supprimer");
        btnCarouselDelete.setForeground(Color.RED);
        btnCarouselDelete.addActionListener(e -> {
            int row = carouselTable.getSelectedRow();
            if (row != -1) {
                int confirm = mainFrame.showCustomConfirmDialog(
                    "Voulez-vous vraiment supprimer cette section du carrousel ?", 
                    "Confirmation"
                );
                if (confirm == JOptionPane.YES_OPTION) {
                    int modelRow = carouselTable.convertRowIndexToModel(row);
                    int id = (int) carouselTableModel.getValueAt(modelRow, 0);
                    
                    deleteCarouselFromDB(id);
                    carouselTableModel.removeRow(modelRow);
                    updateCarouselOrder();
                    saveCarouselOrderToDB(); // Synchronise le nouvel ordre en BDD
                }
            } else {
                mainFrame.showCustomMessageDialog("Sélectionnez une section à supprimer.");
            }
        });

        carouselActions.add(btnCarouselUp); carouselActions.add(btnCarouselDown);
        carouselActions.add(btnCarouselAdd); carouselActions.add(btnCarouselEdit);
        carouselActions.add(btnCarouselDelete);
        carouselTop.add(carouselActions, BorderLayout.EAST);

        String[] carCols = new String[]{"ID", "Ordre", "Titre / Texte", "Chemin Image", "Lien Promo"};
            
        carouselTableModel = new DefaultTableModel(new Object[0][5], carCols) {
            @Override public boolean isCellEditable(int r, int c) { return false; }
        };
        carouselTable = new JTable(carouselTableModel);
        carouselTable.setRowHeight(35);
        
        // On cache la colonne ID technique tout en la laissant accessible dans le modèle (index 0)
        carouselTable.getColumnModel().removeColumn(carouselTable.getColumnModel().getColumn(0));
        carouselTable.getColumnModel().getColumn(0).setMaxWidth(50); // Ajustement de la colonne "Ordre"
        carouselTable.setSelectionBackground(mainFrame.CYNA_BLEU);
        carouselTable.setSelectionForeground(Color.WHITE);

        carouselPanel.add(carouselTop, BorderLayout.NORTH);
        JScrollPane carScroll = new JScrollPane(carouselTable);
        carScroll.setPreferredSize(new Dimension(0, 200));
        carouselPanel.add(carScroll, BorderLayout.CENTER);

        // 3 : CONTENEUR DU TEXTE FIXE INFÉRIEUR
        JPanel textPanel = new JPanel(new BorderLayout(0, 10));
        textPanel.setOpaque(false);
        
        JLabel lblFixedText = new JLabel("📝 Texte fixe sous le carrousel");
        textPanel.add(lblFixedText, BorderLayout.NORTH);

        fixedTextArea = new JTextArea();
        fixedTextArea.setLineWrap(true); fixedTextArea.setWrapStyleWord(true);
        fixedTextArea.setBorder(new EmptyBorder(10, 10, 10, 10));

        JPanel textSavePanel = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        textSavePanel.setOpaque(false);
        JButton btnSaveText = new JButton("Sauvegarder le texte");
        btnSaveText.addActionListener(e -> {
            saveSettingsToDB(fixedTextArea.getText());
            mainFrame.showCustomMessageDialog("Texte fixe mis à jour !");
        });
        textSavePanel.add(btnSaveText);

        textPanel.add(new JScrollPane(fixedTextArea), BorderLayout.CENTER);
        textPanel.add(textSavePanel, BorderLayout.SOUTH);

        add(carouselPanel, BorderLayout.NORTH);
        add(textPanel, BorderLayout.CENTER);

        loadCarouselFromDB();
        loadSettingsFromDB();
    }

    // 4 : LOGIQUE DE RÉORDONNANCEMENT VISUEL

    /**
     * Déplace une ligne du carrousel vers le haut ou le bas et met à jour la base de données.
     */
    private void moveCarouselRow(int direction) {
        int row = carouselTable.getSelectedRow();
        if (row == -1) return;
        int targetRow = row + direction;
        if (targetRow >= 0 && targetRow < carouselTableModel.getRowCount()) {
            carouselTableModel.moveRow(row, row, targetRow);
            carouselTable.setRowSelectionInterval(targetRow, targetRow);
            updateCarouselOrder();
            saveCarouselOrderToDB();
        }
    }

    /**
     * Recalcule séquentiellement la colonne "Ordre" visible à l'écran après un déplacement.
     */
    private void updateCarouselOrder() {
        for (int i = 0; i < carouselTableModel.getRowCount(); i++) {
            carouselTableModel.setValueAt(i + 1, i, 1);
        }
    }

    // 5 : FENÊTRE DE DIALOGUE MODALE (FORMULAIRE)
    private void openCarouselEditDialog(int viewRowIndex) {
        boolean isNew = (viewRowIndex == -1);
        int modelRowIndex = isNew ? -1 : carouselTable.convertRowIndexToModel(viewRowIndex);
        
        String dialogTitle = isNew ? "Nouvelle Section" : "Modifier la Section";

        JDialog dialog = new JDialog(mainFrame, dialogTitle, true);
        dialog.setSize(450, 240); 
        dialog.setLocationRelativeTo(mainFrame); 
        dialog.setLayout(new BorderLayout());

        // Grille standard à 3 lignes de saisie (Texte, Image, Lien)
        JPanel formPanel = new JPanel(new GridLayout(3, 2, 10, 10));
        formPanel.setBorder(new EmptyBorder(20, 20, 20, 20)); 
        formPanel.setOpaque(false);

        formPanel.add(new JLabel("Texte / Titre :"));
        JTextField txtTitle = new JTextField(isNew ? "" : carouselTableModel.getValueAt(modelRowIndex, 2).toString());
        formPanel.add(txtTitle);

        formPanel.add(new JLabel("Image :"));
        // Champ image + bouton d'import : copie le fichier choisi dans assets/img/slides
        // et renseigne automatiquement le chemin relatif (ex. "slides/ma-slide.svg").
        JTextField txtImg = new JTextField(isNew ? "" : carouselTableModel.getValueAt(modelRowIndex, 3).toString());
        JButton btnChooseImg = new JButton("Choisir…");
        btnChooseImg.addActionListener(ev -> {
            String baseName = txtTitle.getText().toLowerCase().trim()
                .replaceAll("[^a-z0-9\\s-]", "").replaceAll("\\s+", "-");
            if (baseName.isBlank()) baseName = "slide";
            String rel = ImageImporter.chooseAndCopy(dialog, "slides", baseName);
            if (rel != null) txtImg.setText(rel);
        });
        JPanel imgCell = new JPanel(new BorderLayout(6, 0));
        imgCell.setOpaque(false);
        imgCell.add(txtImg, BorderLayout.CENTER);
        imgCell.add(btnChooseImg, BorderLayout.EAST);
        formPanel.add(imgCell);

        formPanel.add(new JLabel("Lien :"));
        JTextField txtLink = new JTextField(isNew ? "/index.php?route=" : carouselTableModel.getValueAt(modelRowIndex, 4).toString());
        formPanel.add(txtLink);

        JButton btnSave = new JButton("Enregistrer");
        btnSave.addActionListener(e -> {
            String text = txtTitle.getText();

            if (isNew) {
                addCarouselToDB(carouselTableModel.getRowCount() + 1, text, txtImg.getText(), txtLink.getText());
            } else {
                int id = (int) carouselTableModel.getValueAt(modelRowIndex, 0);
                updateCarouselInDB(id, text, txtImg.getText(), txtLink.getText());
            }
            loadCarouselFromDB(); 
            dialog.dispose();
        });

        JPanel btnPanel = new JPanel(new FlowLayout(FlowLayout.RIGHT)); 
        btnPanel.setOpaque(false); btnPanel.add(btnSave);
        
        dialog.add(formPanel, BorderLayout.CENTER); dialog.add(btnPanel, BorderLayout.SOUTH);
        dialog.getContentPane().setBackground(mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : mainFrame.CLAIR_CONTENEUR);
        mainFrame.updateDarkModeRecursively(dialog.getContentPane(), mainFrame.isDarkModeActive); 
        dialog.setVisible(true);
    }

    // 6 : ACCÈS À LA BASE DE DONNÉES (CRUD)
    private void loadSettingsFromDB() {
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement("SELECT valeur FROM reglages WHERE cle = 'home_intro_text'");
             ResultSet rs = ps.executeQuery()) {
            if (rs.next()) {
                fixedTextArea.setText(rs.getString("valeur"));
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private void saveSettingsToDB(String text) {
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement("UPDATE reglages SET valeur = ? WHERE cle = 'home_intro_text'")) {
            ps.setString(1, text);
            ps.executeUpdate();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private void loadCarouselFromDB() {
        carouselTableModel.setRowCount(0);
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement("SELECT id, position, titre, image, lien_url FROM diapositives_accueil ORDER BY position ASC");
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                carouselTableModel.addRow(new Object[]{
                    rs.getInt("id"),
                    rs.getInt("position"),
                    rs.getString("titre"),
                    rs.getString("image"),
                    rs.getString("lien_url")
                });
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private void addCarouselToDB(int position, String titre, String image, String lienUrl) {
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement("INSERT INTO diapositives_accueil (position, titre, image, lien_url, active) VALUES (?, ?, ?, ?, 1)")) {
            ps.setInt(1, position);
            ps.setString(2, titre);
            ps.setString(3, image);
            ps.setString(4, lienUrl);
            ps.executeUpdate();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private void updateCarouselInDB(int id, String titre, String image, String lienUrl) {
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement("UPDATE diapositives_accueil SET titre = ?, image = ?, lien_url = ? WHERE id = ?")) {
            ps.setString(1, titre);
            ps.setString(2, image);
            ps.setString(3, lienUrl);
            ps.setInt(4, id);
            ps.executeUpdate();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private void deleteCarouselFromDB(int id) {
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement("DELETE FROM diapositives_accueil WHERE id = ?")) {
            ps.setInt(1, id);
            ps.executeUpdate();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    /**
     * Sauvegarde groupée (Batch) pour appliquer l'ordre réorganisé à l'ensemble des éléments.
     */
    private void saveCarouselOrderToDB() {
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement("UPDATE diapositives_accueil SET position = ? WHERE id = ?")) {
            for (int i = 0; i < carouselTableModel.getRowCount(); i++) {
                int id = (int) carouselTableModel.getValueAt(i, 0);
                ps.setInt(1, i + 1);
                ps.setInt(2, id);
                ps.addBatch();
            }
            ps.executeBatch();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}