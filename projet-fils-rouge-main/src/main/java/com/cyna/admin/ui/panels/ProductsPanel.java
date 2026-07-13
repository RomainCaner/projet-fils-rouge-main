package com.cyna.admin.ui.panels;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import javax.swing.event.DocumentEvent;
import javax.swing.event.DocumentListener;
import javax.swing.event.TableModelEvent;
import javax.swing.table.DefaultTableModel;
import javax.swing.table.TableRowSorter;
import javax.swing.text.AbstractDocument;
import javax.swing.text.AttributeSet;
import javax.swing.text.BadLocationException;
import javax.swing.text.DocumentFilter;

import com.cyna.admin.database.DBConnection;
import com.cyna.admin.ui.frames.MainFrame;
import com.cyna.admin.ui.utils.ImageImporter;

import java.awt.*;
import java.awt.datatransfer.DataFlavor;
import java.awt.datatransfer.StringSelection;
import java.awt.datatransfer.Transferable;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.List;

public class ProductsPanel extends JPanel {

    private MainFrame mainFrame;
    private DefaultTableModel productTableModel;
    private JTable productTable;
    private TableRowSorter<DefaultTableModel> productSorter;
    
    // Booléen pour empêcher le déclenchement des events SQL lors du chargement des données
    private boolean isUpdatingTable = false;

    // Classe utilitaire pour gérer les catégories dans le menu déroulant
    private class CategoryItem {
        int id; String name;
        public CategoryItem(int id, String name) { this.id = id; this.name = name; }
        @Override public String toString() { return name; }
    }

    public ProductsPanel(MainFrame mainFrame) {
        this.mainFrame = mainFrame;
        setLayout(new BorderLayout(0, 15));
        setBackground(mainFrame.CLAIR_FOND_PRINCIPAL);
        setBorder(new EmptyBorder(20, 30, 20, 30));

        JPanel topPanel = new JPanel(new BorderLayout());
        topPanel.setOpaque(false);

        JPanel searchPanel = new JPanel(new FlowLayout(FlowLayout.LEFT));
        searchPanel.setOpaque(false);
        
        JLabel lblSearch = new JLabel("🔍 Rechercher :");
        lblSearch.setForeground(mainFrame.isDarkModeActive ? Color.WHITE : mainFrame.CLAIR_TEXTE);
        searchPanel.add(lblSearch);
        
        JTextField searchField = new JTextField(15);
        setupTextFieldTheme(searchField);
        searchPanel.add(searchField);

        JButton btnSortPriority = new JButton("⭐ Gérer l'Ordre des Priorités");
        btnSortPriority.addActionListener(e -> openPrioritySortingDialog());
        searchPanel.add(btnSortPriority);

        JPanel actionBar = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        actionBar.setOpaque(false);
        
        JButton btnAdd = new JButton("➕ Nouveau Produit");
        btnAdd.addActionListener(e -> openProductEditDialog(-1));
        actionBar.add(btnAdd);

        JButton btnEdit = new JButton("Modifier");
        btnEdit.addActionListener(e -> {
            int row = productTable.getSelectedRow();
            if (row != -1) openProductEditDialog(row);
            else mainFrame.showCustomMessageDialog("Sélectionnez un produit à modifier.");
        });
        actionBar.add(btnEdit);
        
        JButton btnDelete = new JButton("Supprimer");
        btnDelete.setForeground(Color.RED);
        btnDelete.addActionListener(e -> {
            int viewRowIndex = productTable.getSelectedRow();
            if (viewRowIndex != -1) {
                int confirm = mainFrame.showCustomConfirmDialog(
                    "Voulez-vous vraiment supprimer ce produit ?", 
                    "Confirmation"
                );
                if (confirm == JOptionPane.YES_OPTION) {
                    int modelRowIndex = productTable.convertRowIndexToModel(viewRowIndex);
                    int idDb = (int) productTableModel.getValueAt(modelRowIndex, 0);
                    deleteProductFromDB(idDb);
                    loadProductsFromDB();
                }
            } else {
                mainFrame.showCustomMessageDialog("Sélectionnez un produit à supprimer.");
            }
        });
        actionBar.add(btnDelete);

        topPanel.add(searchPanel, BorderLayout.WEST);
        topPanel.add(actionBar, BorderLayout.EAST);

        // Modèle avec colonnes cachées (ID DB = 0, Rang = 9)
        String[] cols = new String[]{"ID_DB", "Slug/Réf", "Nom du Produit", "Description", "Catégorie", "Prix/Mois (€)", "Statut", "Date", "Prioritaire", "Rang"};
            
        productTableModel = new DefaultTableModel(new Object[0][10], cols) {
            @Override public boolean isCellEditable(int row, int col) { 
                return col == 4 || col == 6 || col == 8; 
            }
            @Override public Class<?> getColumnClass(int columnIndex) {
                if (columnIndex == 9) return Integer.class; 
                return super.getColumnClass(columnIndex);
            }
        };
        
        productTableModel.addTableModelListener(e -> {
            if (isUpdatingTable) return; 
            if (e.getType() == TableModelEvent.UPDATE) {
                int row = e.getFirstRow();
                int col = e.getColumn();
                if (row == -1 || col == -1) return;
                int idDb = (int) productTableModel.getValueAt(row, 0);
                
                SwingUtilities.invokeLater(() -> {
                    if (col == 4) {
                        String newCatName = (String) productTableModel.getValueAt(row, col);
                        updateCategoryInDB(idDb, newCatName);
                    } else if (col == 6) {
                        String newStatusUI = (String) productTableModel.getValueAt(row, col);
                        updateStatusInDB(idDb, newStatusUI);
                    } else if (col == 8) {
                        String newPrioUI = (String) productTableModel.getValueAt(row, col);
                        updatePriorityInDB(idDb, newPrioUI);
                    }
                });
            }
        });

        productTable = new JTable(productTableModel);
        productTable.setRowHeight(35);
        productTable.setGridColor(mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : mainFrame.CLAIR_GRILLE);
        productTable.setSelectionBackground(mainFrame.CYNA_BLEU);
        productTable.setSelectionForeground(Color.WHITE);
        
        if (mainFrame.isDarkModeActive) {
            productTable.setBackground(mainFrame.SOMBRE_CONTENEUR);
            productTable.setForeground(Color.WHITE);
            productTable.getTableHeader().setBackground(mainFrame.SOMBRE_CONTENEUR.darker());
            productTable.getTableHeader().setForeground(Color.WHITE);
        } else {
            productTable.setBackground(Color.WHITE);
            productTable.setForeground(Color.BLACK);
            productTable.getTableHeader().setBackground(mainFrame.CLAIR_FOND_PRINCIPAL);
            productTable.getTableHeader().setForeground(Color.BLACK);
        }
        
        productTable.removeColumn(productTable.getColumnModel().getColumn(9));
        productTable.removeColumn(productTable.getColumnModel().getColumn(0));
        
        JComboBox<String> categoryCombo = new JComboBox<>();
        for (CategoryItem cat : fetchCategories()) {
            categoryCombo.addItem(cat.name);
        }
        applyCustomEditorAndRenderer(categoryCombo, 3);
        
        String[] statusesUI = new String[]{"DISPONIBLE", "MAINTENANCE"};
        JComboBox<String> statusCombo = new JComboBox<>(statusesUI);
        applyCustomEditorAndRenderer(statusCombo, 5);
        
        JComboBox<String> priorityCombo = new JComboBox<>();
        priorityCombo.addItem("Oui");
        priorityCombo.addItem("Non");
        applyCustomEditorAndRenderer(priorityCombo, 7);
        
        productSorter = new TableRowSorter<>(productTableModel);
        productSorter.setSortable(3, false); // Désactive le tri sur la description
        
        productTable.setRowSorter(productSorter);
        productSorter.setSortKeys(List.of(new RowSorter.SortKey(9, SortOrder.ASCENDING))); 

        searchField.getDocument().addDocumentListener(new DocumentListener() {
            private void filter() { productSorter.setRowFilter(RowFilter.regexFilter("(?i)" + searchField.getText())); }
            @Override public void insertUpdate(DocumentEvent e) { filter(); }
            @Override public void removeUpdate(DocumentEvent e) { filter(); }
            @Override public void changedUpdate(DocumentEvent e) { filter(); }
        });

        add(topPanel, BorderLayout.NORTH);
        
        JScrollPane scrollPane = new JScrollPane(productTable);
        scrollPane.getViewport().setBackground(mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : Color.WHITE);
        add(scrollPane, BorderLayout.CENTER);

        loadProductsFromDB();
    }

    private void setupTextFieldTheme(JTextField textField) {
        if (mainFrame.isDarkModeActive) {
            textField.setBackground(mainFrame.SOMBRE_CONTENEUR.brighter());
            textField.setForeground(Color.WHITE);
            textField.setCaretColor(Color.WHITE);
            textField.setBorder(BorderFactory.createLineBorder(new Color(70, 73, 75), 1));
        } else {
            textField.setBackground(Color.WHITE);
            textField.setForeground(Color.BLACK);
        }
        textField.addFocusListener(new java.awt.event.FocusAdapter() {
            @Override
            public void focusGained(java.awt.event.FocusEvent evt) {
                textField.setBorder(BorderFactory.createLineBorder(mainFrame.isDarkModeActive ? mainFrame.CYNA_BLEU : Color.GRAY, 1));
            }
            @Override
            public void focusLost(java.awt.event.FocusEvent evt) {
                textField.setBorder(mainFrame.isDarkModeActive ? BorderFactory.createLineBorder(new Color(70, 73, 75), 1) : UIManager.getBorder("TextField.border"));
            }
        });
    }

    private void applyCustomEditorAndRenderer(JComboBox<String> combo, int viewColumnIndex) {
        combo.setRenderer(new DefaultListCellRenderer() {
            @Override
            public Component getListCellRendererComponent(JList<?> list, Object value, int index, boolean isSelected, boolean cellHasFocus) {
                Component c = super.getListCellRendererComponent(list, value, index, isSelected, cellHasFocus);
                c.setBackground(isSelected ? mainFrame.CYNA_BLEU : (mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : Color.WHITE));
                c.setForeground(mainFrame.isDarkModeActive || isSelected ? Color.WHITE : Color.BLACK);
                return c;
            }
        });

        productTable.getColumnModel().getColumn(viewColumnIndex).setCellEditor(new DefaultCellEditor(combo) {
            @Override
            public Component getTableCellEditorComponent(JTable table, Object value, boolean isSelected, int row, int column) {
                Component c = super.getTableCellEditorComponent(table, value, isSelected, row, column);
                c.setBackground(mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : Color.WHITE);
                c.setForeground(mainFrame.isDarkModeActive ? Color.WHITE : Color.BLACK);
                return c;
            }
        });
    }

    private void applyDialogComboTheme(JComboBox<?> combo) {
        combo.setRenderer(new DefaultListCellRenderer() {
            @Override
            public Component getListCellRendererComponent(JList<?> list, Object value, int index, boolean isSelected, boolean cellHasFocus) {
                Component c = super.getListCellRendererComponent(list, value, index, isSelected, cellHasFocus);
                c.setBackground(isSelected ? mainFrame.CYNA_BLEU : (mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : Color.WHITE));
                c.setForeground(mainFrame.isDarkModeActive || isSelected ? Color.WHITE : Color.BLACK);
                return c;
            }
        });
    }

    private void updateCategoryInDB(int productId, String categoryName) {
        int catId = 1;
        for (CategoryItem cat : fetchCategories()) {
            if (cat.name.equals(categoryName)) { catId = cat.id; break; }
        }
        String query = "UPDATE produits SET categorie_id = ? WHERE id = ?";
        try (Connection conn = DBConnection.getConnection(); PreparedStatement ps = conn.prepareStatement(query)) {
            ps.setInt(1, catId); ps.setInt(2, productId); ps.executeUpdate();
        } catch (Exception e) { e.printStackTrace(); }
    }

    private void updateStatusInDB(int productId, String statusUI) {
        String statusDB = "DISPONIBLE".equalsIgnoreCase(statusUI) ? "available" : "maintenance";
        String query = "UPDATE produits SET disponibilite = ? WHERE id = ?";
        try (Connection conn = DBConnection.getConnection(); PreparedStatement ps = conn.prepareStatement(query)) {
            ps.setString(1, statusDB); ps.setInt(2, productId); ps.executeUpdate();
        } catch (Exception e) { e.printStackTrace(); }
    }

    private void updatePriorityInDB(int productId, String priorityUI) {
        int isPrio = "Oui".equalsIgnoreCase(priorityUI) ? 1 : 0;
        int rank = isPrio == 1 ? 1 : 999;
        String query = "UPDATE produits SET est_mis_en_avant = ?, position_mise_en_avant = ? WHERE id = ?";
        try (Connection conn = DBConnection.getConnection(); PreparedStatement ps = conn.prepareStatement(query)) {
            ps.setInt(1, isPrio); ps.setInt(2, rank); ps.setInt(3, productId); ps.executeUpdate();
        } catch (Exception e) { e.printStackTrace(); }
    }

    // DIALOGUE D'ÉDITION REFAIT EN GRIDBAGLAYOUT
    private void openProductEditDialog(int viewRowIndex) {
        boolean isNew = (viewRowIndex == -1);
        int modelRowIndex = isNew ? -1 : productTable.convertRowIndexToModel(viewRowIndex);
        
        JDialog dialog = new JDialog(mainFrame, isNew ? "Nouveau Produit" : "Modifier le Produit", true);
        dialog.setSize(480, 540); 
        dialog.setLocationRelativeTo(mainFrame);
        dialog.setLayout(new BorderLayout());

        JPanel formPanel = new JPanel(new GridBagLayout());
        formPanel.setBorder(new EmptyBorder(25, 25, 25, 25));
        formPanel.setOpaque(false);

        GridBagConstraints gbc = new GridBagConstraints();
        gbc.insets = new Insets(8, 8, 8, 8);
        gbc.fill = GridBagConstraints.HORIZONTAL;

        JTextField txtName = new JTextField(isNew ? "" : productTableModel.getValueAt(modelRowIndex, 2).toString());
        setupTextFieldTheme(txtName);

        JTextField txtSlug = new JTextField(isNew ? "cyn-" : productTableModel.getValueAt(modelRowIndex, 1).toString());
        txtSlug.setEditable(false); 
        setupTextFieldTheme(txtSlug);

        if (isNew) {
            txtName.getDocument().addDocumentListener(new DocumentListener() {
                private void generateSlug() {
                    String cleanName = txtName.getText().toLowerCase().trim()
                        .replaceAll("[^a-z0-9\\s-]", "").replaceAll("\\s+", "-").replaceAll("-+", "-");         
                    txtSlug.setText("cyn-" + cleanName);
                }
                @Override public void insertUpdate(DocumentEvent e) { generateSlug(); }
                @Override public void removeUpdate(DocumentEvent e) { generateSlug(); }
                @Override public void changedUpdate(DocumentEvent e) { generateSlug(); }
            });
        }

        JTextField txtDesc = new JTextField(isNew ? "" : productTableModel.getValueAt(modelRowIndex, 3).toString());
        setupTextFieldTheme(txtDesc);

        List<CategoryItem> categories = fetchCategories();
        JComboBox<CategoryItem> cbCategory = new JComboBox<>(categories.toArray(new CategoryItem[0]));
        if (!isNew) {
            String catName = productTableModel.getValueAt(modelRowIndex, 4).toString();
            for (int i = 0; i < cbCategory.getItemCount(); i++) {
                if (cbCategory.getItemAt(i).name.equals(catName)) { cbCategory.setSelectedIndex(i); break; }
            }
        }
        applyDialogComboTheme(cbCategory);

        JTextField txtPrice = new JTextField(isNew ? "0.00" : productTableModel.getValueAt(modelRowIndex, 5).toString());
        setupTextFieldTheme(txtPrice);
        
        ((AbstractDocument) txtPrice.getDocument()).setDocumentFilter(new DocumentFilter() {
            @Override
            public void insertString(FilterBypass fb, int offset, String string, AttributeSet attr) throws BadLocationException {
                if ((fb.getDocument().getText(0, fb.getDocument().getLength()).substring(0, offset) + string).matches("\\d*\\.?\\d*")) super.insertString(fb, offset, string, attr);
            }
            @Override
            public void replace(FilterBypass fb, int offset, int length, String text, AttributeSet attrs) throws BadLocationException {
                if ((fb.getDocument().getText(0, fb.getDocument().getLength()).substring(0, offset) + text).matches("\\d*\\.?\\d*")) super.replace(fb, offset, length, text, attrs);
            }
        });

        String[] statusesUI = new String[]{"DISPONIBLE", "MAINTENANCE"};
        JComboBox<String> cbStatus = new JComboBox<>(statusesUI);
        if (!isNew) cbStatus.setSelectedItem(productTableModel.getValueAt(modelRowIndex, 6).toString());
        applyDialogComboTheme(cbStatus);

        JCheckBox chkPriority = new JCheckBox("Oui");
        chkPriority.setOpaque(false);
        if (!isNew) {
            String prioStr = productTableModel.getValueAt(modelRowIndex, 8).toString();
            if ("Oui".equalsIgnoreCase(prioStr)) chkPriority.setSelected(true);
        }

        // Montage GridBagLayout de l'interface
        gbc.gridx = 0; gbc.gridy = 0; gbc.weightx = 0.3;
        formPanel.add(new JLabel("Nom :"), gbc);
        gbc.gridx = 1; gbc.weightx = 0.7;
        formPanel.add(txtName, gbc);

        gbc.gridx = 0; gbc.gridy = 1; gbc.weightx = 0.3;
        formPanel.add(new JLabel("Slug / Réf :"), gbc);
        gbc.gridx = 1; gbc.weightx = 0.7;
        formPanel.add(txtSlug, gbc);

        gbc.gridx = 0; gbc.gridy = 2; gbc.weightx = 0.3;
        formPanel.add(new JLabel("Description :"), gbc);
        gbc.gridx = 1; gbc.weightx = 0.7;
        formPanel.add(txtDesc, gbc);

        gbc.gridx = 0; gbc.gridy = 3; gbc.weightx = 0.3;
        formPanel.add(new JLabel("Catégorie :"), gbc);
        gbc.gridx = 1; gbc.weightx = 0.7;
        formPanel.add(cbCategory, gbc);

        gbc.gridx = 0; gbc.gridy = 4; gbc.weightx = 0.3;
        formPanel.add(new JLabel("Prix (€) :"), gbc);
        gbc.gridx = 1; gbc.weightx = 0.7;
        formPanel.add(txtPrice, gbc);

        gbc.gridx = 0; gbc.gridy = 5; gbc.weightx = 0.3;
        formPanel.add(new JLabel("Statut :"), gbc);
        gbc.gridx = 1; gbc.weightx = 0.7;
        formPanel.add(cbStatus, gbc);

        gbc.gridx = 0; gbc.gridy = 6; gbc.weightx = 0.3;
        formPanel.add(new JLabel("Prioritaire :"), gbc);
        gbc.gridx = 1; gbc.weightx = 0.7;
        formPanel.add(chkPriority, gbc);

        // Image du produit : conserve le chemin relatif (ex. "products/mon-produit.svg").
        // Pour une modification, on récupère l'image déjà enregistrée en base.
        final String[] imageHolder = { isNew ? null : fetchProductImage((int) productTableModel.getValueAt(modelRowIndex, 0)) };
        JLabel lblImageValue = new JLabel((imageHolder[0] == null || imageHolder[0].isBlank()) ? "Aucune image" : imageHolder[0]);
        JButton btnChooseImage = new JButton("Choisir une image…");
        btnChooseImage.addActionListener(ev -> {
            String rel = ImageImporter.chooseAndCopy(dialog, "products", txtSlug.getText());
            if (rel != null) { imageHolder[0] = rel; lblImageValue.setText(rel); }
        });
        JPanel imagePanel = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 0));
        imagePanel.setOpaque(false);
        imagePanel.add(btnChooseImage);
        imagePanel.add(lblImageValue);

        gbc.gridx = 0; gbc.gridy = 7; gbc.weightx = 0.3;
        formPanel.add(new JLabel("Image :"), gbc);
        gbc.gridx = 1; gbc.weightx = 0.7;
        formPanel.add(imagePanel, gbc);

        JButton btnSave = new JButton("Enregistrer");
        btnSave.addActionListener(e -> {
            try {
                CategoryItem selectedCat = (CategoryItem) cbCategory.getSelectedItem();
                int catId = selectedCat != null ? selectedCat.id : 1;
                double prixEuros = Double.parseDouble(txtPrice.getText().isEmpty() ? "0" : txtPrice.getText());
                int prixCentimes = (int) (prixEuros * 100);
                String statusDB = cbStatus.getSelectedIndex() == 0 ? "available" : "maintenance";
                int isPrio = chkPriority.isSelected() ? 1 : 0;
                int rank = isNew ? 999 : (int) productTableModel.getValueAt(modelRowIndex, 9);
                if (!chkPriority.isSelected()) rank = 999; 

                if (isNew) saveProductToDB(-1, txtSlug.getText(), txtName.getText(), txtDesc.getText(), catId, prixCentimes, statusDB, isPrio, rank, imageHolder[0]);
                else saveProductToDB((int) productTableModel.getValueAt(modelRowIndex, 0), txtSlug.getText(), txtName.getText(), txtDesc.getText(), catId, prixCentimes, statusDB, isPrio, rank, imageHolder[0]);
                
                loadProductsFromDB();
                dialog.dispose();
            } catch (Exception ex) { ex.printStackTrace(); }
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

    // DIALOGUE DE TRI DES PRIORITÉS (HARMONISÉ MODE SOMBRE)
    private void openPrioritySortingDialog() {
        JDialog dialog = new JDialog(mainFrame, "Gérer l'ordre des Produits Prioritaires", true);
        dialog.setSize(420, 420);
        dialog.setLocationRelativeTo(mainFrame);
        dialog.setLayout(new BorderLayout(10, 10));

        JPanel contentPanel = new JPanel(new BorderLayout(10, 10));
        contentPanel.setBorder(new EmptyBorder(15, 15, 15, 15));
        contentPanel.setOpaque(false);
        
        JLabel lblInfo = new JLabel("Réorganisez les produits prioritaires (de 1 à X) :");
        lblInfo.setForeground(mainFrame.isDarkModeActive ? Color.WHITE : mainFrame.CLAIR_TEXTE);
        contentPanel.add(lblInfo, BorderLayout.NORTH);

        DefaultListModel<String> listModel = new DefaultListModel<>();
        List<Object[]> priorityItems = new ArrayList<>();
        
        for (int i = 0; i < productTableModel.getRowCount(); i++) {
            String prioStr = productTableModel.getValueAt(i, 8).toString();
            if ("Oui".equalsIgnoreCase(prioStr)) {
                priorityItems.add(new Object[]{productTableModel.getValueAt(i, 0), productTableModel.getValueAt(i, 2), productTableModel.getValueAt(i, 9)});
            }
        }
        priorityItems.sort((a, b) -> Integer.compare((int)a[2], (int)b[2]));
        for (Object[] item : priorityItems) listModel.addElement(item[0] + " - " + item[1].toString());

        JList<String> list = new JList<>(listModel);
        list.setSelectionMode(ListSelectionModel.SINGLE_SELECTION);
        list.setSelectionBackground(mainFrame.CYNA_BLEU);
        list.setSelectionForeground(Color.WHITE);
        
        if (mainFrame.isDarkModeActive) {
            list.setBackground(mainFrame.SOMBRE_CONTENEUR);
            list.setForeground(Color.WHITE);
        } else {
            list.setBackground(Color.WHITE);
            list.setForeground(Color.BLACK);
        }

        list.setDragEnabled(true);
        list.setDropMode(DropMode.INSERT);
        list.setTransferHandler(new ListReorderTransferHandler());

        JScrollPane listScrollPane = new JScrollPane(list);
        listScrollPane.getViewport().setBackground(mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : Color.WHITE);
        contentPanel.add(listScrollPane, BorderLayout.CENTER);

        JPanel btnArrowPanel = new JPanel(new GridLayout(2, 1, 5, 5));
        btnArrowPanel.setOpaque(false);
        JButton btnUp = new JButton("⬆");
        JButton btnDown = new JButton("⬇");
        
        btnUp.addActionListener(e -> {
            int index = list.getSelectedIndex();
            if (index > 0) {
                String val = listModel.remove(index);
                listModel.add(index - 1, val);
                list.setSelectedIndex(index - 1);
            }
        });
        btnDown.addActionListener(e -> {
            int index = list.getSelectedIndex();
            if (index >= 0 && index < listModel.getSize() - 1) {
                String val = listModel.remove(index);
                listModel.add(index + 1, val);
                list.setSelectedIndex(index + 1);
            }
        });

        btnArrowPanel.add(btnUp); btnArrowPanel.add(btnDown);
        contentPanel.add(btnArrowPanel, BorderLayout.EAST);

        JButton btnSave = new JButton("Sauvegarder l'Ordre");
        btnSave.addActionListener(e -> {
            try (Connection conn = DBConnection.getConnection()) {
                String updateQuery = "UPDATE produits SET position_mise_en_avant = ? WHERE id = ?";
                try (PreparedStatement ps = conn.prepareStatement(updateQuery)) {
                    for (int i = 0; i < listModel.getSize(); i++) {
                        String val = listModel.getElementAt(i);
                        int dbId = Integer.parseInt(val.split(" - ")[0]);
                        ps.setInt(1, i + 1);
                        ps.setInt(2, dbId);
                        ps.addBatch();
                    }
                    ps.executeBatch();
                }
            } catch (Exception ex) { ex.printStackTrace(); }
            loadProductsFromDB();
            dialog.dispose();
        });

        JPanel bottomPanel = new JPanel(new FlowLayout(FlowLayout.RIGHT));
        bottomPanel.setOpaque(false); 
        bottomPanel.setBorder(new EmptyBorder(0, 0, 12, 15));
        bottomPanel.add(btnSave);
        
        dialog.add(contentPanel, BorderLayout.CENTER); 
        dialog.add(bottomPanel, BorderLayout.SOUTH);
        dialog.getContentPane().setBackground(mainFrame.isDarkModeActive ? mainFrame.SOMBRE_CONTENEUR : mainFrame.CLAIR_CONTENEUR);
        mainFrame.updateDarkModeRecursively(dialog.getContentPane(), mainFrame.isDarkModeActive);
        dialog.setVisible(true);
    }

    // Handler Drag & Drop nettoyé
    private class ListReorderTransferHandler extends TransferHandler {
        private int indexSource = -1;

        @Override
        public int getSourceActions(JComponent c) { return TransferHandler.MOVE; }

        @Override
        protected Transferable createTransferable(JComponent c) {
            JList<?> list = (JList<?>) c;
            indexSource = list.getSelectedIndex();
            Object value = list.getSelectedValue();
            return new StringSelection(value != null ? value.toString() : "");
        }

        @Override
        public boolean canImport(TransferSupport support) {
            return support.isDrop() && support.isDataFlavorSupported(DataFlavor.stringFlavor);
        }

        @Override
        @SuppressWarnings("unchecked")
        public boolean importData(TransferSupport support) {
            if (!canImport(support)) return false;

            JList<?> list = (JList<?>) support.getComponent();
            DefaultListModel<String> model = (DefaultListModel<String>) list.getModel();
            JList.DropLocation dl = (JList.DropLocation) support.getDropLocation();
            int dropTargetIndex = dl.getIndex();

            if (indexSource == -1) return false;

            try {
                String data = (String) support.getTransferable().getTransferData(DataFlavor.stringFlavor);
                if (indexSource == dropTargetIndex || indexSource == dropTargetIndex - 1) return true;

                if (indexSource < dropTargetIndex) {
                    model.insertElementAt(data, dropTargetIndex);
                    model.remove(indexSource);
                    list.setSelectedIndex(dropTargetIndex - 1);
                } else {
                    model.remove(indexSource);
                    model.insertElementAt(data, dropTargetIndex);
                    list.setSelectedIndex(dropTargetIndex);
                }
                indexSource = -1;
                return true;
            } catch (Exception e) { e.printStackTrace(); }
            return false;
        }

        @Override
        protected void exportDone(JComponent c, Transferable data, int action) {
            indexSource = -1;
        }
    }

    // ACCÈS BDD
    private void loadProductsFromDB() {
        isUpdatingTable = true;
        productTableModel.setRowCount(0);
        String query = "SELECT p.id, p.slug, p.nom, p.description_courte, c.nom as categorie_nom, " +
                       "p.prix_mensuel_centimes, p.disponibilite, DATE(p.cree_le) as cree_le, " +
                       "p.est_mis_en_avant, p.position_mise_en_avant " +
                       "FROM produits p " +
                       "LEFT JOIN categories c ON p.categorie_id = c.id " +
                       "ORDER BY p.position_mise_en_avant ASC, p.id DESC";
        
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(query);
             ResultSet rs = ps.executeQuery()) {
             
            while (rs.next()) {
                int idDb = rs.getInt("id");
                String slug = rs.getString("slug");
                String nom = rs.getString("nom");
                String desc = rs.getString("description_courte");
                String catNom = rs.getString("categorie_nom");
                if (catNom == null) catNom = "Non classé";
                
                double prixEuros = rs.getInt("prix_mensuel_centimes") / 100.0;
                String prixFormate = String.format("%.2f", prixEuros);
                
                String statutDB = rs.getString("disponibilite");
                String statutUI = "available".equalsIgnoreCase(statutDB) ? "DISPONIBLE" : "MAINTENANCE";
                
                String date = rs.getString("cree_le");
                int isPrio = rs.getInt("est_mis_en_avant");
                String prioUI = isPrio == 1 ? "Oui" : "Non";
                int rank = rs.getInt("position_mise_en_avant");
                
                productTableModel.addRow(new Object[]{idDb, slug, nom, desc, catNom, prixFormate, statutUI, date, prioUI, rank});
            }
        } catch (Exception e) {
            e.printStackTrace();
        } finally {
            isUpdatingTable = false;
        }
    }

    private List<CategoryItem> fetchCategories() {
        List<CategoryItem> list = new ArrayList<>();
        String query = "SELECT id, nom FROM categories ORDER BY nom";
        try (Connection conn = DBConnection.getConnection(); Statement st = conn.createStatement(); ResultSet rs = st.executeQuery(query)) {
            while (rs.next()) list.add(new CategoryItem(rs.getInt("id"), rs.getString("nom")));
        } catch (Exception e) { e.printStackTrace(); }
        return list;
    }

    private void saveProductToDB(int idDb, String slug, String nom, String desc, int catId, int prixCent, String statut, int isPrio, int rank, String image) {
        String query = (idDb == -1) ?
            "INSERT INTO produits (slug, nom, description_courte, description, categorie_id, prix_mensuel_centimes, disponibilite, est_mis_en_avant, position_mise_en_avant, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)" :
            "UPDATE produits SET slug=?, nom=?, description_courte=?, description=?, categorie_id=?, prix_mensuel_centimes=?, disponibilite=?, est_mis_en_avant=?, position_mise_en_avant=?, image=? WHERE id=?";

        try (Connection conn = DBConnection.getConnection(); PreparedStatement ps = conn.prepareStatement(query)) {
            ps.setString(1, slug); ps.setString(2, nom); ps.setString(3, desc); ps.setString(4, desc);
            ps.setInt(5, catId); ps.setInt(6, prixCent); ps.setString(7, statut); ps.setInt(8, isPrio); ps.setInt(9, rank);
            if (image == null || image.isBlank()) ps.setNull(10, java.sql.Types.VARCHAR); else ps.setString(10, image);
            if (idDb != -1) ps.setInt(11, idDb);
            ps.executeUpdate();
        } catch (Exception e) { e.printStackTrace(); }
    }

    /** Récupère le chemin d'image déjà enregistré pour un produit (utilisé en modification). */
    private String fetchProductImage(int id) {
        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement("SELECT image FROM produits WHERE id = ?")) {
            ps.setInt(1, id);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return rs.getString("image");
            }
        } catch (Exception e) { e.printStackTrace(); }
        return null;
    }


    private void deleteProductFromDB(int idDb) {
        String query = "DELETE FROM produits WHERE id = ?";
        try (Connection conn = DBConnection.getConnection(); PreparedStatement ps = conn.prepareStatement(query)) {
            ps.setInt(1, idDb);
            ps.executeUpdate();
        } catch (Exception e) { e.printStackTrace(); }
    }
}