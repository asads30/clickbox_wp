<div class="hcf_box">
    <style scoped>
        .hcf_box{
            display: grid;
            grid-template-columns: max-content 1fr;
            grid-row-gap: 10px;
            grid-column-gap: 20px;
        }
        .hcf_field{
            display: contents;
        }
    </style>
    <p class="meta-options hcf_field">
        <label for="box-length">Длина</label>
        <input id="box-length" type="text" name="box-length" required value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'box-length', true ) ); ?>">
    </p>
    <p class="meta-options hcf_field">
        <label for="box-width">Ширина</label>
        <input id="box-width" type="text" name="box-width" required value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'box-width', true ) ); ?>">
    </p>
    <p class="meta-options hcf_field">
        <label for="box-height">Высота</label>
        <input id="box-height" type="text" name="box-height" required value="<?php echo esc_attr( get_post_meta( get_the_ID(), 'box-height', true ) ); ?>">
    </p>
    <p class="meta-options hcf_field">
        <label for="box-select">Ячейка</label>
        <select name="box-select" id="box-select">
            <option value="1">S</option>
            <option value="2">M</option>
            <option value="3">L</option>
            <option value="4">XL</option>
        </select>
    </p>
</div>