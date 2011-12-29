function nullmodel = tonullmodel(adjmat, m)

    nullmodel = zeros(size(adjmat));
    for i = 1:rows(adjmat),
        for j = 1:columns(adjmat),
            nullmodel(i,j) = columns(find(adjmat(i,:))) * rows(find(adjmat(:,j))) / m;
        end;
    end;
