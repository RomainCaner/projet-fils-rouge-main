package com.cyna.admin.ui.utils;

import javax.swing.*;
import javax.swing.table.DefaultTableModel;
import javax.swing.RowSorter;
import java.awt.datatransfer.DataFlavor;
import java.awt.datatransfer.StringSelection;
import java.awt.datatransfer.Transferable;

// Gère le glisser-déposer (Drag & Drop) des lignes au sein d'un JTable.
public class TableRowTransferHandler extends TransferHandler {
    
    private final JTable table;

    public TableRowTransferHandler(JTable table) {
        this.table = table;
    }

    @Override
    public int getSourceActions(JComponent c) {
        return MOVE;
    }

    @Override
    protected Transferable createTransferable(JComponent c) {
        return new StringSelection(String.valueOf(table.getSelectedRow()));
    }

    @Override
    public boolean canImport(TransferSupport info) {
        return info.isDataFlavorSupported(DataFlavor.stringFlavor);
    }

    @Override
    public boolean importData(TransferSupport info) {
        try {
            // 1. Récupération de la position où l'utilisateur veut déposer la ligne (la destination)
            int targetRow = ((JTable.DropLocation) info.getDropLocation()).getRow();
            
            // 2. Récupération de l'index de la ligne qu'on a "attrapée" au début (la source)
            int draggedRow = Integer.parseInt((String) info.getTransferable().getTransferData(DataFlavor.stringFlavor));
            
            // Sécurité : Si aucune ligne n'est sélectionnée ou si on dépose la ligne exactement 
            // là où elle était, on ne fait rien pour économiser les ressources.
            if (draggedRow == -1 || draggedRow == targetRow) {
                return false;
            }

            // Récupération du modèle de données en mémoire (là où se trouvent les vraies infos du tableau)
            DefaultTableModel model = (DefaultTableModel) table.getModel();
            
            // 1 : Conversion sécurisée de la source
            int modelDraggedRow = table.convertRowIndexToModel(draggedRow);
            
            // 2 : Gestion de la dépose tout en bas
            int modelTargetRow;
            if (targetRow >= table.getRowCount()) {
                modelTargetRow = model.getRowCount(); 
            } else {
                modelTargetRow = table.convertRowIndexToModel(targetRow);
            }
            
            // 3 : Neutralisation temporaire du Tri (RowSorter)
            RowSorter<? extends javax.swing.table.TableModel> sorter = table.getRowSorter();
            if (sorter != null) {
                sorter.setSortKeys(null);
            }
            
            // 4 : Ajustement mathématique du décalage
            if (modelDraggedRow < modelTargetRow) {
                modelTargetRow--;
            }

            // 5 : Le déplacement effectif
            model.moveRow(modelDraggedRow, modelDraggedRow, modelTargetRow);
            
            // 6 : Restauration visuelle de la sélection
            int newVisualRow = table.convertRowIndexToView(modelTargetRow);
            if (newVisualRow != -1) {
                table.setRowSelectionInterval(newVisualRow, newVisualRow);
            }
            
            return true;
            
        } catch (Exception e) {
            // En cas d'erreur inattendue (ex: problème de conversion), on l'affiche dans la console
            e.printStackTrace();
        }
        
        return false;
    }
}