package com.cyna.admin.ui.panels;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import javax.swing.event.DocumentEvent;
import javax.swing.event.DocumentListener;
import javax.swing.table.DefaultTableModel;
import javax.swing.table.TableRowSorter;

import com.cyna.admin.database.DBConnection;
import com.cyna.admin.ui.frames.MainFrame;

import java.awt.*;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;

public class OrdersPanel extends JPanel {

    // 1 : ATTRIBUTS ET CONFIGURATION DU PANNEAU
    private MainFrame mainFrame;
    private DefaultTableModel orderTableModel;
    private JTable orderTable;
    private TableRowSorter<DefaultTableModel> orderSorter;

    public OrdersPanel(MainFrame mainFrame) {
        this.mainFrame = mainFrame;
        setLayout(new BorderLayout(0, 15));
        setBackground(mainFrame.CLAIR_FOND_PRINCIPAL);
        setBorder(new EmptyBorder(20, 30, 20, 30));

        // 2 : BARRE SUPÉRIEURE (RECHERCHE FILTRÉE ET ACTIONS)
        JPanel topPanel = new JPanel(new BorderLayout());
        topPanel.setOpaque(false);

        // Zone de recherche active
        JPanel searchPanel = new JPanel(new FlowLayout(FlowLayout.LEFT));
        searchPanel.setOpaque(false);
        
        JLabel lblSearch = new JLabel("🔍 Rechercher Commande :");
        lblSearch.setForeground(mainFrame.isDarkModeActive ? Color.WHITE : mainFrame.CLAIR_TEXTE);
        searchPanel.add(lblSearch);
        
        JTextField searchField = new JTextField(25);
        if (mainFrame.isDarkModeActive) {
            searchField.setBackground(mainFrame.SOMBRE_CONTENEUR.brighter());
            searchField.setForeground(Color.WHITE);
            searchField.setCaretColor(Color.WHITE);
        }
        searchPanel.add(searchField);

        // Barre d'actions à droite
        JPanel actionBar = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        actionBar.setOpaque(false);
        
        // Bouton de modification via Popup
        JButton btnEditOrder = new JButton("Modifier Statut");
        btnEditOrder.addActionListener(e -> {
            int row = orderTable.getSelectedRow();
            if (row != -1) openOrderEditDialog(row);
            else mainFrame.showCustomMessageDialog("Sélectionnez une commande.");
        });
        actionBar.add(btnEditOrder);

        // Bouton d'annulation globale
        JButton btnCancel = new JButton("Annuler Commande");
        btnCancel.setForeground(Color.RED);
        btnCancel.addActionListener(e -> {
            int viewRow = orderTable.getSelectedRow();
            if (viewRow != -1) {
                int confirm = mainFrame.showCustomConfirmDialog("Annuler cette commande ?", "Confirmation");
                if (confirm == JOptionPane.YES_OPTION) {
                    int modelRow = orderTable.convertRowIndexToModel(viewRow);
                    int idDb = (int) orderTableModel.getValueAt(modelRow, 0); 
                    
                    updateOrderStatusInDB(idDb, "cancelled"); 
                    mainFrame.getInstallationsPanel().removeInstallationRow(idDb); // Désynchronise du volet installations
                    loadOrdersFromDB(); 
                }
            } else {
                mainFrame.showCustomMessageDialog("Sélectionnez une commande à annuler.");
            }
        });
        actionBar.add(btnCancel);

        topPanel.add(searchPanel, BorderLayout.WEST);
        topPanel.add(actionBar, BorderLayout.EAST);

        // 3 : CONFIGURATION DU TABLEAU ET SYNCHRONISATION EN DIRECT
        String[] cols = new String[]{"ID_DB", "Réf Commande", "Client", "Date", "Montant Total (€)", "Statut Paiement"};
            
        orderTableModel = new DefaultTableModel(new Object[0][6], cols) {
            @Override 
            public boolean isCellEditable(int r, int c) { 
                return c == 5; // Édition directe en double-cliquant autorisée uniquement sur la colonne 5 (Statut)
            } 
        };

        // Écouteur sur le modèle pour sauvegarder automatiquement les modifications faites directement dans la cellule
        orderTableModel.addTableModelListener(e -> {
            if (e.getType() == javax.swing.event.TableModelEvent.UPDATE) {
                int row = e.getFirstRow();
                int col = e.getColumn();
                
                // Si la colonne modifiée est bien le Statut (Index 5 dans le Modèle)
                if (col == 5) {
                    int idDb = (int) orderTableModel.getValueAt(row, 0);
                    String newStatusUI = (String) orderTableModel.getValueAt(row, 5);
                    String dbStatus = mapUIToDBStatut(newStatusUI);
                    
                    SwingUtilities.invokeLater(() -> {
                        updateOrderStatusInDB(idDb, dbStatus);
                        
                        // Si la commande passe à PAYÉ, on l'envoie automatiquement au panneau de déploiement (Installations)
                        if ("PAYÉ".equalsIgnoreCase(newStatusUI)) {
                            mainFrame.getInstallationsPanel().addInstallationRow(idDb);
                        } else {
                            mainFrame.getInstallationsPanel().removeInstallationRow(idDb);
                        }
                    });
                }
            }
        });

        orderTable = new JTable(orderTableModel);
        orderTable.setRowHeight(35);
        orderTable.setGridColor(mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : mainFrame.CLAIR_GRILLE);
        orderTable.setSelectionBackground(mainFrame.CYNA_BLEU);
        orderTable.setSelectionForeground(Color.WHITE);

        if (mainFrame.isDarkModeActive) {
            orderTable.setBackground(mainFrame.SOMBRE_CONTENEUR);
            orderTable.setForeground(Color.WHITE);
            orderTable.getTableHeader().setBackground(mainFrame.SOMBRE_CONTENEUR.darker());
            orderTable.getTableHeader().setForeground(Color.WHITE);
        } else {
            orderTable.setBackground(Color.WHITE);
            orderTable.setForeground(Color.BLACK);
            orderTable.getTableHeader().setBackground(mainFrame.CLAIR_FOND_PRINCIPAL);
            orderTable.getTableHeader().setForeground(Color.BLACK);
        }

        // Dissimulation visuelle de la colonne ID (L'index de Statut Paiement passe alors de 5 à 4 dans le modèle de Vue)
        orderTable.getColumnModel().removeColumn(orderTable.getColumnModel().getColumn(0));

        // Injecteur du JComboBox pour l'édition "Inline" du statut dans la cellule
        String[] tableStatuses = new String[]{"PAYÉ", "EN ATTENTE", "ANNULÉ"};
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

        // Application de l'éditeur sur la colonne 4 de la vue (qui correspond à la colonne 5 du modèle)
        orderTable.getColumnModel().getColumn(4).setCellEditor(new DefaultCellEditor(comboEditor) {
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
        
        // Moteur de tri et de filtrage en temps réel
        orderSorter = new TableRowSorter<>(orderTableModel);
        orderTable.setRowSorter(orderSorter);
        
        searchField.getDocument().addDocumentListener(new DocumentListener() {
            private void filter() { orderSorter.setRowFilter(RowFilter.regexFilter("(?i)" + searchField.getText())); }
            @Override public void insertUpdate(DocumentEvent e) { filter(); }
            @Override public void removeUpdate(DocumentEvent e) { filter(); }
            @Override public void changedUpdate(DocumentEvent e) { filter(); }
        });
        
        add(topPanel, BorderLayout.NORTH);
        
        JScrollPane scrollPane = new JScrollPane(orderTable);
        scrollPane.getViewport().setBackground(mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : Color.WHITE);
        add(scrollPane, BorderLayout.CENTER);

        loadOrdersFromDB();
    }

    // 4 : FENÊTRE DE DIALOGUE MODALE (GRIDBAGLAYOUT)
    private void openOrderEditDialog(int viewRowIndex) {
        // Conversion indispensable de l'index de tri/vue vers le modèle sous-jacent
        int modelRowIndex = orderTable.convertRowIndexToModel(viewRowIndex);
        
        int idDb = (int) orderTableModel.getValueAt(modelRowIndex, 0); 
        String currentStatusUI = orderTableModel.getValueAt(modelRowIndex, 5).toString();
        
        JDialog dialog = new JDialog(mainFrame, "Statut Commande", true);
        dialog.setSize(420, 300); 
        dialog.setLocationRelativeTo(mainFrame); 
        dialog.setLayout(new BorderLayout());
        
        JPanel formPanel = new JPanel(new GridBagLayout());
        formPanel.setBorder(new EmptyBorder(25, 25, 25, 25));
        formPanel.setOpaque(false);
        
        GridBagConstraints gbc = new GridBagConstraints();
        gbc.insets = new Insets(8, 8, 8, 8);
        gbc.fill = GridBagConstraints.HORIZONTAL;
        
        JTextField txtRef = new JTextField(orderTableModel.getValueAt(modelRowIndex, 1).toString()); 
        txtRef.setEditable(false); 
        
        JTextField txtClient = new JTextField(orderTableModel.getValueAt(modelRowIndex, 2).toString()); 
        txtClient.setEditable(false); 
        
        String[] statusesUI = new String[]{"PAYÉ", "EN ATTENTE", "ANNULÉ"};
        JComboBox<String> cbStatus = new JComboBox<>(statusesUI); 
        cbStatus.setSelectedItem(currentStatusUI); 
        
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

        // Positionnement GridBagLayout
        gbc.gridx = 0; gbc.gridy = 0; gbc.weightx = 0.3;
        formPanel.add(new JLabel("Réf :"), gbc);
        gbc.gridx = 1; gbc.weightx = 0.7;
        formPanel.add(txtRef, gbc);
        
        gbc.gridx = 0; gbc.gridy = 1; gbc.weightx = 0.3;
        formPanel.add(new JLabel("Client :"), gbc);
        gbc.gridx = 1; gbc.weightx = 0.7;
        formPanel.add(txtClient, gbc);
        
        gbc.gridx = 0; gbc.gridy = 2; gbc.weightx = 0.3;
        formPanel.add(new JLabel("Statut Paiement :"), gbc);
        gbc.gridx = 1; gbc.weightx = 0.7;
        formPanel.add(cbStatus, gbc);
        
        JButton btnSave = new JButton("Mettre à jour"); 
        btnSave.addActionListener(e -> { 
            String newStatusUI = cbStatus.getSelectedItem().toString();
            String dbStatus = mapUIToDBStatut(newStatusUI);
            
            updateOrderStatusInDB(idDb, dbStatus);
            
            boolean isNowPaid = "PAYÉ".equalsIgnoreCase(newStatusUI);
            boolean wasPaid = "PAYÉ".equalsIgnoreCase(currentStatusUI);
            
            // Logique d'inter-communication : On pousse/retire de la liste d'installation selon le changement de statut
            if (isNowPaid && !wasPaid) {
                mainFrame.getInstallationsPanel().addInstallationRow(idDb);
            } else if (!isNowPaid && wasPaid) {
                mainFrame.getInstallationsPanel().removeInstallationRow(idDb);
            }
            
            loadOrdersFromDB();
            dialog.dispose(); 
        });
        
        JPanel btnPanel = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        btnPanel.setOpaque(false);
        btnPanel.setBorder(new EmptyBorder(0, 0, 15, 20));
        btnPanel.add(btnSave);
        
        dialog.add(formPanel, BorderLayout.CENTER); 
        dialog.add(btnPanel, BorderLayout.SOUTH);
        
        dialog.getContentPane().setBackground(mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : mainFrame.CLAIR_CONTENEUR); 
        mainFrame.updateDarkModeRecursively(dialog.getContentPane(), mainFrame.isDarkModeActive); 
        dialog.setVisible(true);
    }

    // 5 : ACCÈS À LA BASE DE DONNÉES (CRUD)
    private void loadOrdersFromDB() {
        orderTableModel.setRowCount(0);
        String query = "SELECT id, numero_facture, facturation_nom, cree_le, total_centimes, statut FROM commandes ORDER BY cree_le DESC";
        
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(query);
             ResultSet rs = ps.executeQuery()) {
             
            while (rs.next()) {
                int id = rs.getInt("id");
                String ref = rs.getString("numero_facture");
                String client = rs.getString("facturation_nom");
                String date = rs.getString("cree_le");
                
                // Passage des centimes de la BDD vers le format monétaire double standard (ex: 1500 -> 15.00)
                double totalEuros = rs.getInt("total_centimes") / 100.0;
                String totalFormate = String.format("%.2f", totalEuros);
                
                String statutUI = mapDBToUIStatut(rs.getString("statut"));
                
                orderTableModel.addRow(new Object[]{id, ref, client, date, totalFormate, statutUI});
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private void updateOrderStatusInDB(int id, String statutDB) {
        String query = "UPDATE commandes SET statut = ? WHERE id = ?";
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(query)) {
            ps.setString(1, statutDB);
            ps.setInt(2, id);
            ps.executeUpdate();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    // 6 : UTILITAIRES DE MAPPAGE DE STATUT (BDD EN <-> INTERFACE FR)
    private String mapDBToUIStatut(String dbStatut) {
        if (dbStatut == null) return "EN ATTENTE";
        switch (dbStatut.toLowerCase()) {
            case "paid":
            case "active":
            case "renewed":
                return "PAYÉ";
            case "cancelled":
            case "failed":
                return "ANNULÉ";
            case "pending":
            default:
                return "EN ATTENTE";
        }
    }

    private String mapUIToDBStatut(String uiStatut) {
        if (uiStatut == null) return "pending";
        if ("PAYÉ".equalsIgnoreCase(uiStatut)) return "paid";
        if ("ANNULÉ".equalsIgnoreCase(uiStatut)) return "cancelled";
        return "pending";
    }
}