<div class="search-box" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; margin-bottom: 25px;">
    <form method="GET" action="index.php" style="display: flex; align-items: center; gap: 15px;">
        <div>
            <label for="date_debut" style="font-weight: bold; margin-right: 10px;">Rapport en date du :</label>
            <input type="date" id="date_debut" name="date_debut" 
                   value="<?php echo $_GET['date_debut'] ?? date('Y-m-d', strtotime('-1 month')); ?>" 
                   style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        <button type="submit" style="padding: 8px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
        Actualiser
        </button>
    </form>
</div>
