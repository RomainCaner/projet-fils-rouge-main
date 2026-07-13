package com.cyna.admin.ui.panels;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import javax.swing.table.DefaultTableModel;
import javax.swing.table.TableRowSorter;

import com.cyna.admin.database.DBConnection;
import com.cyna.admin.ui.frames.MainFrame;

import java.awt.*;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;

public class InstallationsPanel extends JPanel {

    // 1 : ATTRIBUTS ET CONFIGURATION DU PANNEAU
    private MainFrame mainFrame;
    private DefaultTableModel installTableModel;
    private JTable installTable;
    private TableRowSorter<DefaultTableModel> installSorter;

    public InstallationsPanel(MainFrame mainFrame) {
        this.mainFrame = mainFrame;
        setLayout(new BorderLayout(0, 15));
        setBackground(mainFrame.CLAIR_FOND_PRINCIPAL);
        setBorder(new EmptyBorder(20, 30, 20, 30));

        // 2 : BARRE SUPÉRIEURE (TITRE ET ACTIONS)
        JPanel topPanel = new JPanel(new BorderLayout());
        topPanel.setOpaque(false);

        JPanel infoPanel = new JPanel(new FlowLayout(FlowLayout.LEFT));
        infoPanel.setOpaque(false);
        JLabel info = new JLabel("🔧 Suivi des installations (Commandes payées) :");
        info.setFont(new Font("SansSerif", Font.BOLD, 14));
        info.setForeground(mainFrame.isDarkModeActive ? Color.WHITE : mainFrame.CLAIR_TEXTE);
        infoPanel.add(info);

        JPanel actionBar = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        actionBar.setOpaque(false);

        JButton btnEdit = new JButton("Modifier");
        btnEdit.addActionListener(e -> {
            int row = installTable.getSelectedRow();
            if (row != -1) openInstallEditDialog(row);
            else mainFrame.showCustomMessageDialog("Sélectionnez une installation.");
        });
        actionBar.add(btnEdit);

        topPanel.add(infoPanel, BorderLayout.WEST);
        topPanel.add(actionBar, BorderLayout.EAST);
        add(topPanel, BorderLayout.NORTH);

        // 3 : CONFIGURATION DU TABLEAU ET ÉCOUTEURS D'ÉDITION
        String[] cols = new String[]{"ID_DB", "Réf Commande", "Client", "Date/Heure", "Statut Installation", "Commentaires"};
            
        installTableModel = new DefaultTableModel(new Object[0][6], cols) {
            @Override 
            public boolean isCellEditable(int r, int c) { 
                return c == 4; // Édition directe en double-cliquant active uniquement sur la colonne Statut
            } 
        };
        
        // Sauvegarde automatique lors d'une modification directe du statut dans la cellule du tableau
        installTableModel.addTableModelListener(e -> {
            if (e.getType() == javax.swing.event.TableModelEvent.UPDATE) {
                int row = e.getFirstRow(); // Index basé sur le MODÈLE
                int col = e.getColumn();   // Index basé sur le MODÈLE
                
                if (col == 4) {
                    int commandeId = (int) installTableModel.getValueAt(row, 0);
                    String uiStatus = (String) installTableModel.getValueAt(row, 4);
                    
                    String dbStatus = getStatusDB(uiStatus); // Conversion FR UI -> EN BDD
                    String comments = installTableModel.getValueAt(row, 5) != null ? installTableModel.getValueAt(row, 5).toString() : "";
                    
                    SwingUtilities.invokeLater(() -> {
                        updateInstallationInDB(commandeId, dbStatus, comments);
                    });
                }
            }
        });
        
        installTable = new JTable(installTableModel);
        installTable.setRowHeight(35);
        installTable.setGridColor(mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : mainFrame.CLAIR_GRILLE);
        installTable.setSelectionBackground(mainFrame.CYNA_BLEU);
        installTable.setSelectionForeground(Color.WHITE);
        
        // Mode sombre / clair structurel
        if (mainFrame.isDarkModeActive) {
            installTable.setBackground(mainFrame.SOMBRE_CONTENEUR);
            installTable.setForeground(Color.WHITE);
            installTable.getTableHeader().setBackground(mainFrame.SOMBRE_CONTENEUR.darker());
            installTable.getTableHeader().setForeground(Color.WHITE);
        } else {
            installTable.setBackground(Color.WHITE);
            installTable.setForeground(Color.BLACK);
            installTable.getTableHeader().setBackground(mainFrame.CLAIR_FOND_PRINCIPAL);
            installTable.getTableHeader().setForeground(Color.BLACK);
        }
        
        // Suppression visuelle de la colonne ID de la vue utilisateur (reste accessible dans le modèle)
        installTable.getColumnModel().removeColumn(installTable.getColumnModel().getColumn(0));

        String[] tableStatuses = new String[]{"EN ATTENTE", "EN COURS", "INSTALLÉ", "BLOQUÉ"};
        JComboBox<String> comboEditor = new JComboBox<>(tableStatuses);
        
        comboEditor.setRenderer(new DefaultListCellRenderer() {
            @Override
            public Component getListCellRendererComponent(JList<?> list, Object value, int index, boolean isSelected, boolean cellHasFocus) {
                Component c = super.getListCellRendererComponent(list, value, index, isSelected, cellHasFocus);
                if (mainFrame.isDarkModeActive) {
                    c.setBackground(isSelected ? mainFrame.CYNA_BLEU : mainFrame.SOMBRE_CONTENEUR);
                    c.setForeground(Color.WHITE);
                } else {
                    c.setBackground(isSelected ? mainFrame.CYNA_BLEU : Color.WHITE);
                    c.setForeground(isSelected ? Color.WHITE : Color.BLACK);
                }
                return c;
            }
        });

        // Attribution de l'éditeur sur l'index visuel 3 (équivaut à la colonne Statut après suppression de l'ID à l'écran)
        installTable.getColumnModel().getColumn(3).setCellEditor(new DefaultCellEditor(comboEditor) {
            @Override
            public Component getTableCellEditorComponent(JTable table, Object value, boolean isSelected, int row, int column) {
                Component c = super.getTableCellEditorComponent(table, value, isSelected, row, column);
                if (mainFrame.isDarkModeActive) {
                    c.setBackground(mainFrame.SOMBRE_CONTENEUR);
                    c.setForeground(Color.WHITE);
                } else {
                    c.setBackground(Color.WHITE);
                    c.setForeground(Color.BLACK);
                }
                return c;
            }
        });
        
        // Système de tri par défaut (descendant sur la date)
        installSorter = new TableRowSorter<>(installTableModel);
        installTable.setRowSorter(installSorter);
        installSorter.setSortable(5, false); // Pas de tri sur la colonne commentaires
        installSorter.setSortKeys(java.util.List.of(new RowSorter.SortKey(2, SortOrder.DESCENDING))); 

        JScrollPane scrollPane = new JScrollPane(installTable);
        scrollPane.getViewport().setBackground(mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : Color.WHITE);
        add(scrollPane, BorderLayout.CENTER);

        loadInstallationsFromDB();
    }

    // 4 : MÉTHODES INTER-PANNEAUX (ACCESSIBLES DEPUIS ORDERSPANEL)

    /**
     * Insère une nouvelle ligne de déploiement lorsqu'une commande passe à l'état 'PAYÉ'.
     */
    public void addInstallationRow(int commandeIdDb) {
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement("INSERT IGNORE INTO installations (commande_id, statut_installation) VALUES (?, 'PENDING')")) {
            ps.setInt(1, commandeIdDb);
            ps.executeUpdate();
            loadInstallationsFromDB();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    /**
     * Supprime la ligne si la commande liée change de statut ou est annulée.
     */
    public void removeInstallationRow(int commandeIdDb) {
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement("DELETE FROM installations WHERE commande_id = ?")) {
            ps.setInt(1, commandeIdDb);
            ps.executeUpdate();
            loadInstallationsFromDB();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    // 5 : FENÊTRE DE DIALOGUE MODALE (GRIDBAGLAYOUT)
    private void openInstallEditDialog(int viewRowIndex) {
        int modelRowIndex = installTable.convertRowIndexToModel(viewRowIndex);
        int commandeId = (int) installTableModel.getValueAt(modelRowIndex, 0); 
        
        JDialog dialog = new JDialog(mainFrame, "Modifier l'installation", true);
        dialog.setSize(480, 380); 
        dialog.setLocationRelativeTo(mainFrame); 
        dialog.setLayout(new BorderLayout());

        JPanel formPanel = new JPanel(new GridBagLayout());
        formPanel.setBorder(new EmptyBorder(20, 20, 20, 20));
        formPanel.setOpaque(false);
        
        GridBagConstraints gbc = new GridBagConstraints();
        gbc.insets = new Insets(8, 8, 8, 8);
        gbc.fill = GridBagConstraints.HORIZONTAL;

        JTextField txtRef = new JTextField(installTableModel.getValueAt(modelRowIndex, 1).toString()); 
        txtRef.setEditable(false);
        
        JTextField txtClient = new JTextField(installTableModel.getValueAt(modelRowIndex, 2).toString()); 
        txtClient.setEditable(false);
        
        String[] statuses = new String[]{"EN ATTENTE", "EN COURS", "INSTALLÉ", "BLOQUÉ"};
        JComboBox<String> cbStatus = new JComboBox<>(statuses); 
        cbStatus.setSelectedItem(installTableModel.getValueAt(modelRowIndex, 4).toString()); 
        
        cbStatus.setRenderer(new DefaultListCellRenderer() {
            @Override
            public Component getListCellRendererComponent(JList<?> list, Object value, int index, boolean isSelected, boolean cellHasFocus) {
                Component c = super.getListCellRendererComponent(list, value, index, isSelected, cellHasFocus);
                if (mainFrame.isDarkModeActive) {
                    c.setBackground(isSelected ? mainFrame.CYNA_BLEU : mainFrame.SOMBRE_CONTENEUR);
                    c.setForeground(Color.WHITE);
                } else {
                    c.setBackground(isSelected ? mainFrame.CYNA_BLEU : Color.WHITE);
                    c.setForeground(isSelected ? Color.WHITE : Color.BLACK);
                }
                return c;
            }
        });
        
        String existingComments = installTableModel.getValueAt(modelRowIndex, 5) != null ? installTableModel.getValueAt(modelRowIndex, 5).toString() : "";
        JTextArea txtComments = new JTextArea(existingComments);
        txtComments.setLineWrap(true); txtComments.setWrapStyleWord(true);
        JScrollPane scrollComments = new JScrollPane(txtComments);
        scrollComments.setPreferredSize(new Dimension(200, 80));

        // Placement GridBagLayout
        gbc.gridx = 0; gbc.gridy = 0; gbc.weightx = 0.3;
        formPanel.add(new JLabel("Réf Commande :"), gbc);
        gbc.gridx = 1; gbc.weightx = 0.7;
        formPanel.add(txtRef, gbc);

        gbc.gridx = 0; gbc.gridy = 1; gbc.weightx = 0.3;
        formPanel.add(new JLabel("Client :"), gbc);
        gbc.gridx = 1; gbc.weightx = 0.7;
        formPanel.add(txtClient, gbc);

        gbc.gridx = 0; gbc.gridy = 2; gbc.weightx = 0.3;
        formPanel.add(new JLabel("Statut Installation :"), gbc);
        gbc.gridx = 1; gbc.weightx = 0.7;
        formPanel.add(cbStatus, gbc);

        gbc.gridx = 0; gbc.gridy = 3; gbc.weightx = 0.3; gbc.anchor = GridBagConstraints.NORTHWEST;
        formPanel.add(new JLabel("Commentaires :"), gbc);
        gbc.gridx = 1; gbc.weightx = 0.7; gbc.fill = GridBagConstraints.BOTH; gbc.weighty = 1.0;
        formPanel.add(scrollComments, gbc);

        JButton btnSave = new JButton("Mettre à jour"); 
        btnSave.addActionListener(e -> { 
            String uiStatus = cbStatus.getSelectedItem().toString();
            String dbStatus = getStatusDB(uiStatus); 
            
            updateInstallationInDB(commandeId, dbStatus, txtComments.getText());
            loadInstallationsFromDB(); 
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
    private void loadInstallationsFromDB() {
        installTableModel.setRowCount(0); 
        
        String query = "SELECT c.id, c.numero_facture, c.facturation_nom, c.cree_le, i.statut_installation, i.commentaires " +
                       "FROM installations i " +
                       "JOIN commandes c ON i.commande_id = c.id";
                       
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(query);
             ResultSet rs = ps.executeQuery()) {
             
            while (rs.next()) {
                String dbStatus = rs.getString("statut_installation");
                String uiStatus = getStatusUI(dbStatus); 

                installTableModel.addRow(new Object[]{
                    rs.getInt("id"),
                    rs.getString("numero_facture"),
                    rs.getString("facturation_nom"),
                    rs.getString("cree_le"),
                    uiStatus,
                    rs.getString("commentaires")
                });
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private void updateInstallationInDB(int commandeId, String statut, String commentaires) {
        String query = "UPDATE installations SET statut_installation = ?, commentaires = ? WHERE commande_id = ?";
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(query)) {
            ps.setString(1, statut);
            ps.setString(2, commentaires);
            ps.setInt(3, commandeId);
            ps.executeUpdate();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    // 7 : UTILITAIRES DE MAPPAGE DE STATUT (BDD EN <-> INTERFACE FR)
    private String getStatusUI(String dbStatus) {
        if (dbStatus == null) return "EN ATTENTE";
        switch (dbStatus.toUpperCase()) {
            case "PENDING": return "EN ATTENTE";
            case "IN PROGRESS": return "EN COURS";
            case "INSTALLED": return "INSTALLÉ";
            case "ON HOLD": return "BLOQUÉ";
            default: return dbStatus;
        }
    }

    private String getStatusDB(String uiStatus) {
        if (uiStatus == null) return "PENDING";
        switch (uiStatus.toUpperCase()) {
            case "EN ATTENTE": return "PENDING";
            case "EN COURS": return "IN PROGRESS";
            case "INSTALLÉ": return "INSTALLED";
            case "BLOQUÉ": return "ON HOLD";
            default: return uiStatus;
        }
    }
}